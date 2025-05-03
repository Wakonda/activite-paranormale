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
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;

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

	public function __construct(UserPasswordHasherInterface $encoderFactory, TokenStorageInterface $tokenStorage) {
		$this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
	}

    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils, EntityManagerInterface $em)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

		$lastUser = $em->getRepository(User::class)->findUserByUsernameOrEmail($lastUsername);

		return $this->render('user/Security/login.html.twig', [
				'error'         => $error,
				'last_username' => $lastUsername,
				'last_user' => $lastUser
		]);
    }

    public function showAction()
    {
		$user = $this->getUser();

        if (!is_object($user)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('user/Profile/show.html.twig', [
            'user' => $user
        ]);
    }
	
	public function phpbbAction(Request $request, TranslatorInterface $translator, \App\Service\PHPBB $phpbb) {
		$user = $this->getUser();

        if (!is_object($user)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
		
		$form = $this->createFormBuilder();
		
		foreach($phpbb->getLanguages() as $language) {
			$form->add("password".$language, \Symfony\Component\Form\Extension\Core\Type\PasswordType::class);
		}
		
		$form = $form->getForm();
		
		if($request->isMethod('post')) {
			$form->handleRequest($request);
			$language = key($request->request->get("language"));
			$password = $form->get("password".$language)->getData();

			if(empty($password))
				$this->addFlash("error", $translator->trans("user.phpbb.ErrorPasswordEmpty", [], "validators"));
			else {
				$token = $phpbb->getJWT($language);
				$user = $this->getUser();
				$res = $phpbb->saveUser($token, $user->getUsername(), $password, $user->getEmail());

				if(isset($res["error"]))
					$this->addFlash("error", $translator->trans("user.phpbb.ErrorCreateAccount", [], "validators")." [".$res["error"]."]");
				else
					$this->addFlash("success", $translator->trans("user.phpbb.Success", [], "validators")." [".$res["success"]."]");
			}
			
			return $this->redirect($this->generateUrl("Profile_Show"));
		}
		
        return $this->render('user/Profile/phpbb.html.twig', [
			'forums' => $phpbb->getForumsByUser($user->getUsername()),
			"form" => $form->createView()
        ]);
	}

    public function requestAction()
    {
        return $this->render('user/Resetting/request.html.twig');
    }
	
    public function editAction(Request $request, EntityManagerInterface $em)
    {
		$user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createForm(EditProfileType::class, $user, ["locale" => $request->getLocale()]);

        return $this->render('user/EditProfile/edit.html.twig', [
			'user' => $user,
            'form'   => $editForm->createView(),
        ]);
    }

    public function updateAction(Request $request, EntityManagerInterface $em)
    {
		$user = $this->getUser();

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
			$user->setAvatar($photo);
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('Profile_Show'));
        }

        return $this->render('user/EditProfile/edit.html.twig', [
            'user' => $user,
            'form' => $editForm->createView(),
        ]);
    }

    public function sendEmailAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, MailerInterface $mailer)
    {
        $username = $request->request->get('username');

        $user = $em->getRepository(User::class)->findUserByUsernameOrEmail($username);

        if (null !== $user and (!$user->isPasswordRequestNonExpired($this->retryTtl) or empty($user->getPassword()))) {
            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->generateToken());
            }

            $user->setPasswordRequestedAt(new \DateTime());

            $em->persist($user);
            $em->flush();
			
			$url = $this->generateUrl('Resetting_Reset', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);

			$email = (new Email())
				->from($_ENV["MAILER_USER"])
				->to($user->getEmail())
				->subject($translator->trans("resetting.email.subject", [], 'FOSUserBundle'))
				->html($this->renderView('user/Resetting/email.txt.twig', ['user' => $user, 'confirmationUrl' => $url]));

			$mailer->send($email);
        }

        return $this->redirect($this->generateUrl('Resetting_Check_Email', ['username' => $username]));
    }

    public function checkEmailResettingAction(Request $request)
    {
        $username = $request->query->get('username');

        if (empty($username)) {
            return new RedirectResponse($this->generateUrl('Resetting_Request'));
        }

        return $this->render('user/Resetting/check_email.html.twig', [
            'tokenLifetime' => ceil($this->retryTtl / 3600),
        ]);
    }

    public function resetAction(Request $request, EntityManagerInterface $em, $token)
    {
        $user = $em->getRepository(User::class)->findUserByConfirmationToken($token);

        if (null === $user) {
            return $this->redirect($this->generateUrl('Security_Login'));
        }
		
		$form = $this->createForm(ResettingFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
			$salt = base64_encode(random_bytes(32));
			$user->setSalt($salt);
			$user->setPassword($this->encoderFactory->hashPassword($user, $form->get("password")->getData(), $salt));
            $em->persist($user);
            $em->flush();

			return $this->redirect($this->generateUrl('Profile_Show'));
        }

        return $this->render('user/Resetting/reset.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    public function registerAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, MailerInterface $mailer)
    {
		$session = $request->getSession();
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
				$user->setPassword($this->encoderFactory->hashPassword($user, $form->get("password")->getData(), $salt));
				
				$em->persist($user);
				$em->flush();

				$url = $this->generateUrl('Registration_Confirm', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);

				$email = (new Email())
					->from($_ENV["MAILER_USER"])
					->to($user->getEmail())
					->subject($translator->trans("registration.email.subject", ['%username%' => $user->getUsername(), '%confirmationUrl%' => $url], 'FOSUserBundle'))
					->html($this->renderView('user/Registration/email.html.twig', ['user' => $user, 'confirmationUrl' => $url]));

				$mailer->send($email);

				$session->set('fos_user_send_confirmation_email/email', $user->getEmail());

				return $this->redirect($this->generateUrl('Registration_Check_Email'));
            }
        }

        return $this->render('user/Registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

	public function resendEmailConfirmationAction(Request $request, TranslatorInterface $translator, EntityManagerInterface $em, MailerInterface $mailer, $id)
	{
		$session = $request->getSession();
		$user = $em->getRepository(User::class)->find($id);

		$url = $this->generateUrl('Registration_Confirm', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);

		$email = (new Email())
			->from($_ENV["MAILER_USER"])
			->to($user->getEmail())
			->subject($translator->trans("registration.email.subject", ['%username%' => $user->getUsername(), '%confirmationUrl%' => $url], 'FOSUserBundle'))
			->html($this->renderView('user/Registration/email.html.twig', ['user' => $user, 'confirmationUrl' => $url]));

		$mailer->send($email);

		$session->set('fos_user_send_confirmation_email/email', $user->getEmail());

		return $this->redirect($this->generateUrl('Registration_Check_Email'));
	}

    /**
     * Tell the user to check their email provider.
     */
    public function checkEmailAction(Request $request, EntityManagerInterface $em, UrlGeneratorInterface $router)
    {
		$session = $request->getSession();
        $email = $session->get('fos_user_send_confirmation_email/email');

        if (empty($email)) {
            return new RedirectResponse($this->generateUrl('fos_user_registration_register'));
        }

        $session->remove('fos_user_send_confirmation_email/email');
        $user = $em->getRepository(User::class)->findUserByEmail($email);

        if (null === $user) {
            return new RedirectResponse($router->generate('Security_Login'));
        }

        return $this->render('user/Registration/check_email.html.twig', [
            'user' => $user,
        ]);
    }

    public function confirmAction(Request $request, EntityManagerInterface $em, $token)
    {
        $user = $em->getRepository(User::class)->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

		$em->persist($user);
		$em->flush();

        return $this->render('user/Registration/confirmed.html.twig', [
            'user' => $user
        ]);
    }

    public function changePasswordAction(Request $request, EntityManagerInterface $em)
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
			$salt = base64_encode(random_bytes(32));
			$user->setSalt($salt);
			$user->setPassword($this->encoderFactory->hashPassword($user, $form->get("password")->getData(), $salt));

            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl("Profile_Show"));
        }

        return $this->render('user/ChangePassword/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

	public function countUserAction(EntityManagerInterface $em)
	{
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

    public function viewContributionsAction(EntityManagerInterface $em, APUser $apuser, $id)
    {
		$user = $em->getRepository(User::class)->find($id);

		list($contributionsArray, $contributionsInProgressArray, $contributionsUnpublishedArray) = $this->getUserContributions($apuser, $user);

		return $this->render('user/AdminUser/contribution.html.twig', [
			'user' => $user,
			'contributionsArray' => $contributionsArray,
			'contributionsInProgressArray' => $contributionsInProgressArray,
			'contributionsUnpublishedArray' => $contributionsUnpublishedArray
		]);
    }

	public function viewProfileAction(EntityManagerInterface $em, APUser $apuser, $id)
	{
		$user = $em->getRepository(User::class)->find($id);

		list($contributionsArray, $contributionsInProgressArray, $contributionsUnpublishedArray) = $this->getUserContributions($apuser, $user);

		return $this->render('user/AdminUser/other_profile.html.twig', [
			'user' => $user,
			'contributionsArray' => $contributionsArray,
			'contributionsInProgressArray' => $contributionsInProgressArray,
			'contributionsUnpublishedArray' => $contributionsUnpublishedArray
		]);
	}
	
	public function donationUser(Request $request, $user)
	{
		$res = [];

		if(!empty($user->getDonation())) {
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
		}
		
		return $this->render("user/Profile/donation.html.twig", ["donations" => $res, "currencies" => Currency::getCurrencies(), "languageCountry" => Currency::getlanguageCountry($request->getLocale())]);
	}

    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}