<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Photo;
use App\Entity\PhotoTags;
use App\Entity\Language;
use App\Entity\Licence;
use App\Entity\State;
use App\Entity\Theme;
use App\Entity\FileManagement;
use App\Form\Type\PhotoAdminType;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

/**
 * Photo controller.
 *
 */
class PhotoAdminController extends AdminGenericController
{
	protected $entityName = 'Photo';
	protected $className = Photo::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Photo_Admin_Index"; 
	protected $showRoute = "Photo_Admin_Show";
	protected $formName = "ap_photo_photoadmintype";

	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new PhotoTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'photo/PhotoAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'photo/PhotoAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = PhotoAdminType::class;
		$entity = new Photo();

		$twig = 'photo/PhotoAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = PhotoAdminType::class;
		$entity = new Photo();

		$twig = 'photo/PhotoAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = PhotoAdminType::class;

		$twig = 'photo/PhotoAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = PhotoAdminType::class;
		$twig = 'photo/PhotoAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\PhotoComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\PhotoVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\PhotoTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }
	
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
	}
	
	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];
		
		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		foreach($informationArray['entities'] as $entity) {
			$row = [];

			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";

			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $date->doDate($request->getLocale(), $entity->getPublicationDate());
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => $entity->getState()->getInternationalName(), 'language' => $language]);
			$row[] =  $state->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('Photo_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Photo_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadListsByLanguageAction(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language))
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList($language->getAbbreviation(), $request->getLocale());
			$states = $em->getRepository(State::class)->findByLanguage($language, array('title' => 'ASC'));
			$licences = $em->getRepository(Licence::class)->findByLanguage($language, array('title' => 'ASC'));
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList(null, $request->getLocale());
			$states = $em->getRepository(State::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
		}

		$themeArray = [];
		$stateArray = [];
		$licenceArray = [];
		
		foreach($themes as $theme)
			$themeArray[] = ["id" => $theme["id"], "title" => $theme["title"]];

		$translateArray['theme'] = $themeArray;

		foreach($states as $state)
			$stateArray[] = array("id" => $state->getId(), "title" => $state->getTitle());

		$translateArray['state'] = $stateArray;

		foreach($licences as $licence)
			$licenceArray[] = array("id" => $licence->getId(), "title" => $licence->getTitle());

		$translateArray['licence'] = $licenceArray;

		return new JsonResponse($translateArray);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGeneric('Photo_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}
	
	public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
	{
		$formType = PhotoAdminType::class;
		$entity = new Photo();

		$entityToCopy = $em->getRepository(Photo::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$theme = $em->getRepository(Theme::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getTheme()->getInternationalName()]);
		$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getState()->getInternationalName()]);
// dd($state);
		if(empty($state)) {
			$defaultLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
			$state = $em->getRepository(State::class)->findOneBy(["language" => $defaultLanguage, "internationalName" => "Validate"]);
		}

		$entity->setState($state);

		if(!empty($theme))
			$entity->setTheme($theme);

		$entity->setLanguage($language);
		
		if(!empty($ci = $entityToCopy->getIllustration())) {
			$illustration = new FileManagement();
			$illustration->setTitleFile($ci->getTitleFile());
			$illustration->setRealNameFile($ci->getRealNameFile());
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());

			$entity->setIllustration($illustration);
		}

		$request->setLocale($language->getAbbreviation());

		$twig = 'photo/PhotoAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}

	public function countByState(EntityManagerInterface $em, $state)
	{
		$countByStateAdmin = $em->getRepository($this->className)->countByStateAdmin($state);
		return new Response($countByStateAdmin);
	}

	public function deleteMultiple(Request $request, EntityManagerInterface $em)
	{
		$ids = json_decode($request->request->get("ids"));

		$entities = $em->getRepository($this->className)->findBy(['id' => $ids]);

		foreach($entities as $entity)
			$em->remove($entity);

		$em->flush();

		return new Response();
	}

	public function changeStateAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $id, $state)
	{
		$language = $request->getLocale();

		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);

		$entity = $em->getRepository(Photo::class)->find($id);
		
		$entity->setState($state);

		if($state->getInternationalName() == "Validate") {
			if(empty($entity->getTheme()))
				return $this->redirect($this->generateUrl('Photo_Admin_Edit', ['id' => $id]));
		}

		$em->persist($entity);
		$em->flush();

		if($state->getInternationalName() == "Validate")
			$this->addFlash('success', $translator->trans('news.admin.NewsPublished', [], 'validators'));
		else
			$this->addFlash('success', $translator->trans('news.admin.NewsRefused', [], 'validators'));

		return $this->redirect($this->generateUrl('Photo_Admin_Show', ['id' => $id]));
	}
}