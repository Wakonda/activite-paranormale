<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

use App\Entity\Testimony;
use App\Entity\TestimonyTags;
use App\Entity\SurTheme;
use App\Entity\Theme;
use App\Entity\Licence;
use App\Entity\State;
use App\Entity\User;
use App\Entity\Language;
use App\Form\Type\TestimonyUserParticipationType;
use App\Service\APHtml2Pdf;
use App\Service\TagsManagingGeneric;

class TestimonyController extends AbstractController
{
	public function postValidationAction($form, $entityBindded)
	{
		(new TagsManagingGeneric($this->getDoctrine()->getManager()))->saveTags($form, Testimony::class, 'Testimony', new TestimonyTags(), $entityBindded);
	}

    public function indexAction(Request $request)
    {
		$entity = new Testimony();
		$em = $this->getDoctrine()->getManager();
		$language = $request->getLocale();
		
		$SurTheme = $em->getRepository(SurTheme::class)->getSurTheme($language);
		$theme = $em->getRepository(Theme::class)->getTheme($language);

		$entities = $em->getRepository(Testimony::class)->getAllTestimonyByThemeAndLanguage($language);
		$countEntities = $em->getRepository(Testimony::class)->getAllTestimonyByThemeAndLanguage($language, true);

		return $this->render('testimony/Testimony/index.html.twig', array(
			'entity' => $entity,
			'surTheme' => $SurTheme,
			'countEntities' => $countEntities,
			'theme' => $theme
		));
    }
	
	// USER PARTICIPATION
    public function newAction(Request $request)
    {
        $entity = new Testimony();
		$securityUser = $this->container->get('security.authorization_checker');
		
		$entity->setLicence($this->getDoctrine()->getManager()->getRepository(Licence::class)->getOneLicenceByLanguageAndInternationalName($request->getLocale(), "CC-BY-NC-ND"));
		
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'user' => $user, 'securityUser' => $securityUser]);

        return $this->render('testimony/Testimony/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    public function createAction(Request $request, SessionInterface $session)
    {
		return $this->generateCreateUpdate($request, $session);
    }

	public function addFileAction($id, SessionInterface $session)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Testimony::class)->find($id);
		
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$securityUser = $this->container->get('security.authorization_checker');
		
		if($entity->getState()->isStateDisplayed() or (!empty($entity->getAuthor()) and !$securityUser->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and $user->getId() != $entity->getAuthor()->getId()) or $session->get("testimony") != $entity->getId())
			throw new \Exception("You are not authorized to edit this document.");

		return $this->render('testimony/Testimony/addFile.html.twig', array('entity' => $entity));
	}

	public function postCreateAction($id, $draft, $preview)
	{
		return $this->render('testimony/Testimony/validate_externaluser_text.html.twig');
	}

    public function showAction(Request $request, $id, $title_slug)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(Testimony::class)->find($id);

        if (!$entity)
            throw $this->createNotFoundException('Unable to find Testimony entity.');

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(Testimony::class)->getPreviousAndNextEntities($entity, $request->getLocale());

        return $this->render('testimony/Testimony/show.html.twig', array(
			'previousAndNextEntities' => $previousAndNextEntities,
            'entity'      => $entity
        ));
    }

	public function generateCreateUpdate(Request $request, SessionInterface $session, $id = 0)
	{
		$em = $this->getDoctrine()->getManager();
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		
		if(empty($id))
			$entity  = new Testimony();
		else {
			$entity = $em->getRepository(Testimony::class)->find($id);
			
			if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
				throw new \Exception("You are not authorized to edit this document.");

		}

		$securityUser = $this->container->get('security.authorization_checker');
        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'user' => $user, 'securityUser' => $securityUser]);
        $form->handleRequest($request);

		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));
		
		if($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('draft')->isClicked())
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Draft', 'language' => $language));
		elseif($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('preview')->isClicked())
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Draft', 'language' => $language));
		else
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));

		$entity->setState($state);
		$entity->setLanguage($language);

		if(is_object($user))
		{
			$entity->setAuthor($user);
		}
		else
		{
			$anonymousUser = $em->getRepository(User::class)->findOneBy(array('username' => 'Anonymous'));
			$entity->setAuthor($anonymousUser);
			$entity->setIsAnonymous(1);
		}

        if($form->isValid())
		{	
			$em->persist($entity);
			$em->flush();
			
			$this->postValidationAction($form, $entity);
			
			if($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('preview')->isClicked())
			{
				return $this->redirect($this->generateUrl('Testimony_Waiting', array('id' => $entity->getId())));
			}
			elseif($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('draft')->isClicked())
			{
				return $this->redirect($this->generateUrl('Profile_Show'));
			}
			
			$session->set('testimony', $entity->getId());
			
			return $this->redirect($this->generateUrl('Testimony_AddFile', array('id' => $entity->getId())));
        }

        return $this->render('testimony/Testimony/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
	}
	
	public function waitingAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(Testimony::class)->find($id);
		if($entity->getState()->getDisplayState() == 1)
			return $this->redirect($this->generateUrl('Testimony_Show', array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())));

		return $this->render('testimony/Testimony/waiting.html.twig', array(
            'entity' => $entity,
        ));
	}
	
	public function validateAction(Request $request, SessionInterface $session, $id)
	{
		$em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(Testimony::class)->find($id);
		
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$securityUser = $this->container->get('security.authorization_checker');

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		if($entity->getState()->isStateDisplayed() or (!empty($entity->getAuthor()) and !$securityUser->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and $user->getId() != $entity->getAuthor()->getId()) or (!$securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $session->get("testimony") != $entity->getId()))
			throw new \Exception("You are not authorized to edit this document.");
		
		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));
		$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));
		
		$entity->setState($state);
		$em->persist($entity);
		$em->flush();
		
		return $this->render('testimony/Testimony/validate_externaluser_text.html.twig');
	}

    public function editAction(Request $request, $id)
    {
		$securityUser = $this->container->get('security.authorization_checker');
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(Testimony::class)->find($id);

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		if($entity->getState()->getDisplayState() == 1 or ($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $user->getId() != $entity->getAuthor()->getId()))
			throw new \Exception("You are not authorized to edit this article.");

        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'user' => $user, 'securityUser' => $securityUser]);

        return $this->render('testimony/Testimony/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

	public function updateAction($id)
    {
		return $this->genericCreateUpdate($id);
    }
	
	public function tabAction(Request $request, $id, $theme)
	{
		$em = $this->getDoctrine()->getManager();
		$entities = $em->getRepository(Testimony::class)->getTabTestimony($request->getLocale(), $theme);
		
		return $this->render('testimony/Testimony/tab.html.twig', array(
			'themeDisplay' => $theme,
			'id' => $id,
			'entities' => $entities
		));	
	}
	
	/* FONCTION DE COMPTAGE */
	public function countThemeLangTestimonyAction($theme, $lang)
	{
		$em = $this->getDoctrine()->getManager();
		$nbrTestimonyByTheme = $em->getRepository(Testimony::class)->nbrTestimonyByTheme($lang, $theme);
		return new Response($nbrTestimonyByTheme);
	}
	
	public function countAllTestimoniesAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$nbrOfAllTestimonies = $em->getRepository(Testimony::class)->countAllTestimoniesForLeftMenu($request->getLocale());
		return new Response($nbrOfAllTestimonies);
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(APHtml2Pdf $html2pdf, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Testimony::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The testimony does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("testimony/Testimony/pdfVersion.html.twig", array("entity" => $entity));
		
		return $html2pdf->generatePdf($content->getContent());
	}
}