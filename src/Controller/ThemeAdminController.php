<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Theme;
use App\Entity\Language;
use App\Form\Type\ThemeAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Theme controller.
 *
 */
class ThemeAdminController extends AdminGenericController
{
	protected $entityName = 'Theme';
	protected $className = Theme::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Theme_Admin_Index"; 
	protected $showRoute = "Theme_Admin_Show";
	protected $formName = "ap_index_themeadmintype";
	protected $illustrations = [["field" => "photo", 'selectorFile' => 'photo_selector'], ["field" => "pdfTheme", 'selectorFile' => 'pdf_selector']];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'index/ThemeAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'index/ThemeAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = ThemeAdminType::class;
		$entity = new Theme();

		$twig = 'index/ThemeAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = ThemeAdminType::class;
		$entity = new Theme();

		$twig = 'index/ThemeAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = ThemeAdminType::class;

		$twig = 'index/ThemeAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $request->getLocale()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = ThemeAdminType::class;
		
		$twig = 'index/ThemeAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGenericAction($em, $id);
    }

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = !empty($data = $entity->getParentTheme()) ? $data->getTitle() : "-";
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Theme_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Theme_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
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
			$parentThemes = $em->getRepository(Theme::class)->getParentThemeByLanguageForList($language->getAbbreviation(), $request->getLocale());
		else
			$parentThemes = $em->getRepository(Theme::class)->getParentThemeByLanguageForList(null, $request->getLocale());

		$parentThemeArray = [];

		foreach($parentThemes as $parentTheme)
			$parentThemeArray[] = ["id" => $parentTheme["id"], "title" => $parentTheme["title"]];

		$translateArray['parentTheme'] = $parentThemeArray;

		return new JsonResponse($translateArray);
	}

	protected function defaultValueForMappedSuperclassBase(Request $request, EntityManagerInterface $em, $entity)
	{
		$language = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $request->getLocale()));
		$entity->setLanguage($language);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Theme_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}

    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = ThemeAdminType::class;
		$entity = new Theme();

		$entityToCopy = $em->getRepository(Theme::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$parentTheme = $em->getRepository(Theme::class)->findOneBy(["internationalName" => $entityToCopy->getParentTheme()->getInternationalName(), "language" => $language]);

		$entity->setInternationalName($entityToCopy->getInternationalName());
		
		if(!empty($parentTheme))
			$entity->setParentTheme($parentTheme);
		
		$entity->setLanguage($language);
		$entity->setPhoto($entityToCopy->getPhoto());

		$request->setLocale($language->getAbbreviation());

		$twig = 'index/ThemeAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }
}