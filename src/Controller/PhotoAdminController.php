<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

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

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidationAction($form, $entityBindded)
	{
		(new TagsManagingGeneric($this->getDoctrine()->getManager()))->saveTags($form, $this->className, $this->entityName, new PhotoTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'photo/PhotoAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'photo/PhotoAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = PhotoAdminType::class;
		$entity = new Photo();

		$twig = 'photo/PhotoAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = PhotoAdminType::class;
		$entity = new Photo();

		$twig = 'photo/PhotoAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = PhotoAdminType::class;

		$twig = 'photo/PhotoAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = PhotoAdminType::class;
		$twig = 'photo/PhotoAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository("\App\Entity\PhotoComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\PhotoVote")->findBy(["photo" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\PhotoTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($id);
    }
	
	public function archiveAction($id)
	{
		return $this->archiveGenericArchive($id);
	}
	
	public function indexDatatablesAction(Request $request, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $date->doDate($request->getLocale(), $entity->getPublicationDate());
			$row[] = "
			 <a href='".$this->generateUrl('Photo_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Photo_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadListsByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language))
		{
			$themes = $em->getRepository(Theme::class)->findByLanguage($language, array('title' => 'ASC'));
			$states = $em->getRepository(State::class)->findByLanguage($language, array('title' => 'ASC'));
			$licences = $em->getRepository(Licence::class)->findByLanguage($language, array('title' => 'ASC'));
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->findAll();
			$states = $em->getRepository(State::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
		}

		$themeArray = [];
		$stateArray = [];
		$licenceArray = [];
		
		foreach($themes as $theme)
			$themeArray[] = array("id" => $theme->getId(), "title" => $theme->getTitle());

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
		return $this->showImageSelectorColorboxGenericAction('Photo_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}
	
	public function internationalizationAction(Request $request, $id)
	{
		$formType = PhotoAdminType::class;
		$entity = new Photo();

		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(Photo::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$theme = $em->getRepository(Theme::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getTheme()->getInternationalName()]);
		$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getState()->getInternationalName()]);
		
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
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());

			$entity->setIllustration($illustration);
		}

		$request->setLocale($language->getAbbreviation());

		$twig = 'photo/PhotoAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}
}