<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
	public function postValidation(EntityManagerInterface $em, $form, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, Testimony::class, 'Testimony', new TestimonyTags(), $entityBindded);
	}

	#[Route('/testimony', name: 'Testimony_Index')]
    public function index(Request $request, EntityManagerInterface $em)
    {
		$locale = $request->getLocale();

		$entities = $em->getRepository(Testimony::class)->getAllTestimonyByThemeAndLanguage($locale);
		$countEntities = array_sum(array_column($entities, "total"));

		$datas = [];

		foreach($entities as $entity)
			$datas[$entity["parentTheme"]][] = $entity;

		return $this->render('testimony/Testimony/index.html.twig', [
			'datas' => $datas,
			'countEntities' => $countEntities
		]);
    }
	
	// USER PARTICIPATION
	#[Route('/testimony/new', name: 'Testimony_New')]
    public function newAction(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker)
    {
        $entity = new Testimony();

		$entity->setLicence($em->getRepository(Licence::class)->getOneLicenceByLanguageAndInternationalName($request->getLocale(), "CC-BY-NC-ND 3.0"));

        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'securityUser' => $authorizationChecker]);

        return $this->render('testimony/Testimony/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	#[Route('/testimony/create', name: 'Testimony_Create')]
    public function create(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker)
    {
		return $this->generateCreateUpdate($request, $em, $authorizationChecker);
    }

	#[Route('/testimony/file/{id}', name: 'Testimony_AddFile')]
	public function addFile(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker, $id)
	{
		$session = $request->getSession();
		$entity = $em->getRepository(Testimony::class)->find($id);
		
		$user = $this->getUser();
		
		if($entity->getState()->isStateDisplayed() or (!empty($user) and !empty($entity->getAuthor()) and !$authorizationChecker->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and $user->getId() != $entity->getAuthor()->getId()) or $session->get("testimony") != $entity->getId())
			throw new \Exception("You are not authorized to edit this document.");

		return $this->render('testimony/Testimony/addFile.html.twig', array('entity' => $entity));
	}

	#[Route('/testimony/postcreate/{draft}/{preview}/{id}', name: 'Testimony_Postcreate', defaults: ['draft' => 0, 'preview' => 0, 'id' => 0])]
	public function postCreate($id, $draft, $preview)
	{
		return $this->render('testimony/Testimony/validate_externaluser_text.html.twig');
	}

	#[Route('/testimony/show/{id}/{title_slug}', name: 'Testimony_Show', defaults: ['title_slug' => null])]
    public function show(Request $request, EntityManagerInterface $em, $id, $title_slug)
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

	public function generateCreateUpdate(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker, $id = 0)
	{
		$session = $request->getSession();
		$user = $this->getUser();

		if(empty($id))
			$entity  = new Testimony();
		else {
			$entity = $em->getRepository(Testimony::class)->find($id);

			if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
				throw new \Exception("You are not authorized to edit this document.");
		}

        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'securityUser' => $authorizationChecker]);
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

			$this->postValidation($em, $form, $entity);

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

	#[Route('/testimony/waiting/{id}', name: 'Testimony_Waiting')]
	public function waiting(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Testimony::class)->find($id);
		if($entity->getState()->getDisplayState() == 1)
			return $this->redirect($this->generateUrl('Testimony_Show', array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())));

		return $this->render('testimony/Testimony/waiting.html.twig', array(
            'entity' => $entity,
        ));
	}

	#[Route('/testimony/validate/{id}', name: 'Testimony_Validate')]
	public function validate(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker, $id)
	{
		$session = $request->getSession();
        $entity = $em->getRepository(Testimony::class)->find($id);
		
		$user = $this->getUser();

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

	#[Route('/testimony/edit/{id}', name: 'Testimony_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker, $id)
    {
		$user = $this->getUser();
        $entity = $em->getRepository(Testimony::class)->find($id);

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		if($entity->getState()->getDisplayState() == 1 or ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') and $user->getId() != $entity->getAuthor()->getId()))
			throw new \Exception("You are not authorized to edit this article.");

        $form = $this->createForm(TestimonyUserParticipationType::class, $entity, ['locale' => $request->getLocale(), 'securityUser' => $authorizationChecker]);

        return $this->render('testimony/Testimony/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	#[Route('/testimony/update/{id}', name: 'Testimony_Update')]
	public function update($id)
    {
		return $this->genericCreateUpdate($id);
    }

	#[Route('/testimony/tab/{id}/{theme}', name: 'Testimony_Tab', requirements: ['theme' => '.+'])]
	public function tab(Request $request, EntityManagerInterface $em, $id, $theme)
	{
		$entities = $em->getRepository(Testimony::class)->getTabTestimony($request->getLocale(), $theme);
		
		return $this->render('testimony/Testimony/tab.html.twig', [
			'themeDisplay' => $theme,
			'id' => $id,
			'entities' => $entities
		]);
	}
	
	/* FONCTION DE COMPTAGE */
	public function countAllTestimoniesAction(Request $request, EntityManagerInterface $em)
	{
		$nbrOfAllTestimonies = $em->getRepository(Testimony::class)->countAllTestimoniesForLeftMenu($request->getLocale());
		return new Response($nbrOfAllTestimonies);
	}

	// ENREGISTREMENT PDF
	#[Route('/testimony/pdfversion/{id}', name: 'Testimony_Pdfversion')]
	public function pdfVersion(EntityManagerInterface $em, APHtml2Pdf $html2pdf, $id)
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