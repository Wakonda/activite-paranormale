<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Testimony;
use App\Entity\TestimonyTags;
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
	public function postValidationAction(EntityManagerInterface $em, $form, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, Testimony::class, 'Testimony', new TestimonyTags(), $entityBindded);
	}

    public function indexAction(Request $request, EntityManagerInterface $em)
    {
		$entity = new Testimony();
		$locale = $request->getLocale();
		
		$parentTheme = $em->getRepository(Theme::class)->getThemeParent($locale);
		$theme = $em->getRepository(Theme::class)->getTheme($locale);

		$entities = $em->getRepository(Testimony::class)->getAllTestimonyByThemeAndLanguage($locale);
		$countEntities = $em->getRepository(Testimony::class)->getAllTestimonyByThemeAndLanguage($locale, true);

		return $this->render('testimony/Testimony/index.html.twig', [
			'entity' => $entity,
			'parentTheme' => $parentTheme,
			'countEntities' => $countEntities,
			'theme' => $theme
		]);
    }
	
	// USER PARTICIPATION
    public function newAction(Request $request, EntityManagerInterface $em, Security $security, AuthorizationCheckerInterface $authorizationChecker)
    {
        $entity = new Testimony();
		
		$entity->setLicence($em->getRepository(Licence::class)->getOneLicenceByLanguageAndInternationalName($request->getLocale(), "CC-BY-NC-ND 3.0"));
		
		$user = $security->getUser();
        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'user' => $user, 'securityUser' => $authorizationChecker]);

        return $this->render('testimony/Testimony/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    public function createAction(Request $request, EntityManagerInterface $em, Security $security, AuthorizationCheckerInterface $authorizationChecker)
    {
		return $this->generateCreateUpdate($request, $em, $security, $authorizationChecker);
    }

	public function addFileAction(Request $request, EntityManagerInterface $em, Security $security, AuthorizationCheckerInterface $authorizationChecker, $id)
	{
		$session = $request->getSession();
		$entity = $em->getRepository(Testimony::class)->find($id);
		
		$user = $security->getUser();
		
		if($entity->getState()->isStateDisplayed() or (!empty($entity->getAuthor()) and !$authorizationChecker->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and $user->getId() != $entity->getAuthor()->getId()) or $session->get("testimony") != $entity->getId())
			throw new \Exception("You are not authorized to edit this document.");

		return $this->render('testimony/Testimony/addFile.html.twig', array('entity' => $entity));
	}

	public function postCreateAction($id, $draft, $preview)
	{
		return $this->render('testimony/Testimony/validate_externaluser_text.html.twig');
	}

    public function showAction(Request $request, EntityManagerInterface $em, $id, $title_slug)
    {
        $entity = $em->getRepository(Testimony::class)->find($id);

        if (!$entity)
            throw $this->createNotFoundException('Unable to find Testimony entity.');

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(Testimony::class)->getPreviousAndNextEntities($entity, $request->getLocale());

        return $this->render('testimony/Testimony/show.html.twig', [
			'previousAndNextEntities' => $previousAndNextEntities,
            'entity'      => $entity
        ]);
    }

	public function generateCreateUpdate(Request $request, EntityManagerInterface $em, Security $security, AuthorizationCheckerInterface $authorizationChecker, $id = 0)
	{
		$session = $request->getSession();
		$user = $security->getUser();

		if(empty($id))
			$entity  = new Testimony();
		else {
			$entity = $em->getRepository(Testimony::class)->find($id);

			if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
				throw new \Exception("You are not authorized to edit this document.");
		}

        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'user' => $user, 'securityUser' => $authorizationChecker]);
        $form->handleRequest($request);

		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));

		if($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('draft')->isClicked())
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Draft', 'language' => $language));
		elseif($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('preview')->isClicked())
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Draft', 'language' => $language));
		else
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));

		$entity->setState($state);
		$entity->setLanguage($language);

		if(is_object($user) and !$entity->getIsAnonymous())
			$entity->setAuthor($user);
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

			$this->postValidationAction($em, $form, $entity);

			if($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('preview')->isClicked())
				return $this->redirect($this->generateUrl('Testimony_Waiting', array('id' => $entity->getId())));
			elseif($form->get('save')->isClicked())
				return $this->render('testimony/Testimony/validate_externaluser_text.html.twig');
			elseif($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('draft')->isClicked())
				return $this->redirect($this->generateUrl('Profile_Show'));

			$session->set('testimony', $entity->getId());
			
			return $this->redirect($this->generateUrl('Testimony_AddFile', array('id' => $entity->getId())));
        }

        return $this->render('testimony/Testimony/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
	}
	
	public function waitingAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Testimony::class)->find($id);
		if($entity->getState()->getDisplayState() == 1)
			return $this->redirect($this->generateUrl('Testimony_Show', array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())));

		return $this->render('testimony/Testimony/waiting.html.twig', array(
            'entity' => $entity,
        ));
	}
	
	public function validateAction(Request $request, EntityManagerInterface $em, Security $security, AuthorizationCheckerInterface $authorizationChecker, $id)
	{
		$session = $request->getSession();
        $entity = $em->getRepository(Testimony::class)->find($id);
		
		$user = $security->getUser();

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		if($entity->getState()->isStateDisplayed() or (!empty($entity->getAuthor()) and !$authorizationChecker->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and $user->getId() != $entity->getAuthor()->getId()) or (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') and $session->get("testimony") != $entity->getId()))
			throw new \Exception("You are not authorized to edit this document.");
		
		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));
		$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));
		
		$entity->setState($state);
		$em->persist($entity);
		$em->flush();
		
		return $this->render('testimony/Testimony/validate_externaluser_text.html.twig');
	}

    public function editAction(Request $request, EntityManagerInterface $em, Security $security, AuthorizationCheckerInterface $authorizationChecker, $id)
    {
		$user = $security->getUser();
        $entity = $em->getRepository(Testimony::class)->find($id);

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		if($entity->getState()->getDisplayState() == 1 or ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') and $user->getId() != $entity->getAuthor()->getId()))
			throw new \Exception("You are not authorized to edit this article.");

        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'user' => $user, 'securityUser' => $authorizationChecker]);

        return $this->render('testimony/Testimony/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	public function updateAction($id)
    {
		return $this->genericCreateUpdate($id);
    }
	
	public function tabAction(Request $request, EntityManagerInterface $em, $id, $theme)
	{
		$entities = $em->getRepository(Testimony::class)->getTabTestimony($request->getLocale(), $theme);
		
		return $this->render('testimony/Testimony/tab.html.twig', [
			'themeDisplay' => $theme,
			'id' => $id,
			'entities' => $entities
		]);
	}
	
	/* FONCTION DE COMPTAGE */
	public function countThemeLangTestimonyAction(EntityManagerInterface $em, $theme, $lang)
	{
		$nbrTestimonyByTheme = $em->getRepository(Testimony::class)->nbrTestimonyByTheme($lang, $theme);
		return new Response($nbrTestimonyByTheme);
	}
	
	public function countAllTestimoniesAction(Request $request, EntityManagerInterface $em)
	{
		$nbrOfAllTestimonies = $em->getRepository(Testimony::class)->countAllTestimoniesForLeftMenu($request->getLocale());
		return new Response($nbrOfAllTestimonies);
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(EntityManagerInterface $em, APHtml2Pdf $html2pdf, $id)
	{
		$entity = $em->getRepository(Testimony::class)->find($id);

		if(empty($entity))
			throw $this->createNotFoundException("The testimony does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("testimony/Testimony/pdfVersion.html.twig", array("entity" => $entity));
		
		return $html2pdf->generatePdf($content->getContent());
	}
}