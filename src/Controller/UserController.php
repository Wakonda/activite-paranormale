<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\User;
use App\Form\Type\ChangePasswordType;
use App\Form\Type\EditProfileType;
use App\Form\Type\RegistrationFormType;
use App\Form\Type\ResettingFormType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use App\Service\APUser;
use App\Service\Currency;

include_once(__DIR__."/../Library/QRCode.php");

/**
 * User controller.
 *
 */
class UserController extends AbstractController
{
	private $encoderFactory;
	private $retryTtl = 7200;
	private $tokenTtl = 86400;
    private $tokenStorage;

	public function __construct(UserPasswordEncoderInterface $encoderFactory, TokenStorageInterface $tokenStorage) {
		$this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
	}

    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $em = $this->getDoctrine()->getManager();
		$lastUser = $em->getRepository(User::class)->findUserByUsernameOrEmail($lastUsername);

		return $this->render('user/Security/login.html.twig', array(
				'error'         => $error,
				'last_username' => $lastUsername,
				'last_user' => $lastUser
		));
    }

    public function showAction()
    {
		$user = $this->container->get('security.token_storage')->getToken()->getUser();

        if (!is_object($user)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('user/Profile/show.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * Request reset user password: show form.
     */
    public function requestAction()
    {
        return $this->render('user/Resetting/request.html.twig');
    }

    /**
     * @return string|null
     */
    private function getTargetUrlFromSession(SessionInterface $session)
    {
		if(!method_exists($this->tokenStorage->getToken(), "getProviderKey"))
			return null;

        $key = sprintf('_security.%s.target_path', $this->tokenStorage->getToken()->getProviderKey());

        if ($session->has($key)) {
            return $session->get($key);
        }

        return null;
    }
	
    public function editAction(Request $request)
    {
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createForm(EditProfileType::class, $user, ["locale" => $request->getLocale()]);

        return $this->render('user/EditProfile/edit.html.twig', array(
			'user' => $user,
            'form'   => $editForm->createView(),
        ));
    }

    public function updateAction(Request $request)
    {
		$user = $this->container->get('security.token_storage')->getToken()->getUser();

		$photoBDD = $user->getAvatar();

        if (!$user) {
            throw $this->createNotFoundException('Unable to find user entity.');
        }

        $editForm = $this->createForm(EditProfileType::class, $user, ["locale" => $request->getLocale()]);
        $editForm->handleRequest($request);

		$photoForm = $editForm->getData()->getAvatar();

		if($photoForm == "")
			$photo = $photoBDD;
		else
			$photo = $photoForm;

        if ($editForm->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$user->setAvatar($photo);
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('Profile_Show'));
        }

        return $this->render('user/EditProfile/edit.html.twig', array(
            'user'      => $user,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Request reset user password: submit form and send email.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function sendEmailAction(Request $request, TranslatorInterface $translator, \Swift_Mailer $mailer)
    {
        $username = $request->request->get('username');

		$em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findUserByUsernameOrEmail($username);

        if (null !== $user && !$user->isPasswordRequestNonExpired($this->retryTtl)) {

            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->generateToken());
            }

            $user->setPasswordRequestedAt(new \DateTime());

            $em->persist($user);
            $em->flush();
			
			$url = $this->generateUrl('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
			
			$message = (new \Swift_Message($translator->trans("resetting.email.subject", [], 'FOSUserBundle')))
				->setTo($user->getEmail())
				->setFrom($_ENV["MAILER_USER"])
				->setBody($this->renderView('user/Resetting/email.txt.twig', array('user' => $user, 'confirmationUrl' => $url)), 'text/html')
			;

			$mailer->send($message);
        }

        return $this->redirect($this->generateUrl('Resetting_Check_Email', array('username' => $username)));
    }

    /**
     * Tell the user to check his email provider.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkEmailResettingAction(Request $request)
    {
        $username = $request->query->get('username');

        if (empty($username)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->generateUrl('Resetting_Request'));
        }

        return $this->render('user/Resetting/check_email.html.twig', array(
            'tokenLifetime' => ceil($this->retryTtl / 3600),
        ));
    }

    /**
     * Reset user password.
     *
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
    public function resetAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findUserByConfirmationToken($token);

        if (null === $user) {
            return $this->redirect($this->generateUrl('Security_Login'));
        }
		
		$form = $this->createForm(ResettingFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$salt = base64_encode(random_bytes(32));
			$user->setSalt($salt);
			$user->setPassword($this->encoderFactory->encodePassword($user, $form->get("password")->getData(), $salt));
            $em->persist($user);
            $em->flush();
			
			return $this->redirect($this->generateUrl('Profile_Show'));
        }

        return $this->render('user/Resetting/reset.html.twig', array(
            'token' => $token,
            'form' => $form->createView(),
        ));
    }

    public function registerAction(Request $request, TranslatorInterface $translator, SessionInterface $session, \Swift_Mailer $mailer)
    {
		$em = $this->getDoctrine()->getManager();
		$user = new User();
        $user->setEnabled(false);

        $form = $this->createForm(RegistrationFormType::class, $user, ["locale" => $request->getLocale()]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
				$user->setUsernameCanonical();
				$user->setEmailCanonical();
				$user->setConfirmationToken($this->generateToken());
				
				$salt = base64_encode(random_bytes(32));
				$user->setSalt($salt);
				$user->setPassword($this->encoderFactory->encodePassword($user, $form->get("password")->getData(), $salt));
				
				$em->persist($user);
				$em->flush();

				$url = $this->generateUrl('Registration_Confirm', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);

				$message = (new \Swift_Message($translator->trans("registration.email.subject", ['%username%' => $user->getUsername(), '%confirmationUrl%' => $url], 'FOSUserBundle')))
					->setTo($user->getEmail())
					->setFrom($_ENV["MAILER_USER"])
					->setBody($this->renderView('user/Registration/email.html.twig', array('user' => $user, 'confirmationUrl' => $url)), 'text/html')
				;

				$mailer->send($message);
				$session->set('fos_user_send_confirmation_email/email', $user->getEmail());

				return $this->redirect($this->generateUrl('Registration_Check_Email'));
            }
        }

        return $this->render('user/Registration/register.html.twig', array(
            'form' => $form->createView(),
        ));
    }
	
	public function resendEmailConfirmationAction(TranslatorInterface $translator, SessionInterface $session, \Swift_Mailer $mailer, $id)
	{
        $em = $this->getDoctrine()->getManager();
		$user = $em->getRepository(User::class)->find($id);

		$url = $this->generateUrl('Registration_Confirm', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);

		$message = (new \Swift_Message($translator->trans("registration.email.subject", ['%username%' => $user->getUsername(), '%confirmationUrl%' => $url], 'FOSUserBundle')))
			->setTo($user->getEmail())
			->setFrom($_ENV["MAILER_USER"])
			->setBody($this->renderView('user/Registration/email.html.twig', array('user' => $user, 'confirmationUrl' => $url)), 'text/html')
		;

		$mailer->send($message);
		$session->set('fos_user_send_confirmation_email/email', $user->getEmail());

		return $this->redirect($this->generateUrl('Registration_Check_Email'));
	}

    /**
     * Tell the user to check their email provider.
     */
    public function checkEmailAction(Request $request, SessionInterface $session, UrlGeneratorInterface $router)
    {
        $email = $session->get('fos_user_send_confirmation_email/email');

        if (empty($email)) {
            return new RedirectResponse($this->generateUrl('fos_user_registration_register'));
        }

        $session->remove('fos_user_send_confirmation_email/email');
		$em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findUserByEmail($email);

        if (null === $user) {
            return new RedirectResponse($router->generate('Security_Login'));
        }

        return $this->render('user/Registration/check_email.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     *
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
    public function confirmAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

		$em->persist($user);
		$em->flush();

        return $this->render('user/Registration/confirmed.html.twig', array(
            'user' => $user,
            'targetUrl' => $this->getTargetUrlFromSession($request->getSession()),
        ));
    }

    /**
     * Change user password.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function changePasswordAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$salt = base64_encode(random_bytes(32));
			$user->setSalt($salt);
			$user->setPassword($this->encoderFactory->encodePassword($user, $form->get("password")->getData(), $salt));

            $em->persist($user);
            $em->flush();
			
            return $this->redirect($this->generateUrl("Profile_Show"));
        }

        return $this->render('user/ChangePassword/change_password.html.twig', array(
            'form' => $form->createView(),
        ));
    }

	public function countUserAction()
	{
		$em = $this->getDoctrine()->getManager();
		$countUser = $em->getRepository(User::class)->countAdmin();
		return new Response($countUser);
	}

	private function getUserContributions(APUser $apuser, $user)
	{
		$contributionsArray = $apuser->countContributionByUser($user, 1);
		$contributionsInProgressArray = $apuser->countContributionByUser($user, 0);
		$contributionsUnpublishedArray = $apuser->countContributionByUser($user, -1);

		return [$contributionsArray, $contributionsInProgressArray, $contributionsUnpublishedArray];
	}

    public function viewContributionsAction(APUser $apuser, $id)
    {
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository(User::class)->find($id);

		list($contributionsArray, $contributionsInProgressArray, $contributionsUnpublishedArray) = $this->getUserContributions($apuser, $user);
		
		return $this->render('user/AdminUser/contribution.html.twig', array(
			'user' => $user,
			'contributionsArray' => $contributionsArray,
			'contributionsInProgressArray' => $contributionsInProgressArray,
			'contributionsUnpublishedArray' => $contributionsUnpublishedArray
		));	
    }

	public function viewProfileAction(APUser $apuser, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository(User::class)->find($id);
		
		list($contributionsArray, $contributionsInProgressArray, $contributionsUnpublishedArray) = $this->getUserContributions($apuser, $user);
		
		return $this->render('user/AdminUser/other_profile.html.twig', array(
			'user' => $user,
			'contributionsArray' => $contributionsArray,
			'contributionsInProgressArray' => $contributionsInProgressArray,
			'contributionsUnpublishedArray' => $contributionsUnpublishedArray
		));	
	}
	
	public function donationUser(Request $request, $user)
	{
		$res = [];

		foreach(json_decode($user->getDonation(), true) as $donation) {
			if(strtolower($donation["donation"]) == "paypal")
				$res[] = ["title" => $donation["donation"], "address" => $donation["address"], "qrcode" => null];
			else {
				$qrCode = \QRCode::getMinimumQRCode($donation["address"], \QR_ERROR_CORRECT_LEVEL_L);
				
				ob_start();
				imagegif($qrCode->createImage(6, 4));
				$contents = ob_get_contents();
				ob_end_clean();

				$res[] = ["title" => $donation["donation"], "address" => $donation["address"], "qrcode" => base64_encode($contents)];
			}
		}
		
		return $this->render("user/Profile/donation.html.twig", ["donations" => $res, "currencies" => Currency::getCurrencies(), "languageCountry" => Currency::getlanguageCountry($request->getLocale())]);
	}
	
    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}