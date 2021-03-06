<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Theme;
use App\Entity\SurTheme;
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
	protected $illustrations = [["field" => "photo", 'selectorFile' => 'photo_selector']];

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'index/ThemeAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'index/ThemeAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = ThemeAdminType::class;
		$entity = new Theme();

		$twig = 'index/ThemeAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = ThemeAdminType::class;
		$entity = new Theme();

		$twig = 'index/ThemeAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, $id)
    {
		$formType = ThemeAdminType::class;

		$twig = 'index/ThemeAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $request->getLocale()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = ThemeAdminType::class;
		
		$twig = 'index/ThemeAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		return $this->deleteGenericAction($id);
    }

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = array();
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $entity->getSurTheme()->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Theme_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Theme_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadListsByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();

		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = array();
		
		if(!empty($language))
			$surThemes = $em->getRepository(SurTheme::class)->findByLanguage($language, array('title' => 'ASC'));
		else
			$surThemes = $em->getRepository(SurTheme::class)->findAll();

		$surThemeArray = array();

		foreach($surThemes as $surTheme)
		{
			$surThemeArray[] = array("id" => $surTheme->getId(), "title" => $surTheme->getTitle());
		}
		$translateArray['surTheme'] = $surThemeArray;

		return new JsonResponse($translateArray);
	}

	protected function defaultValueForMappedSuperclassBase(Request $request, $entity)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $request->getLocale()));
		$entity->setLanguage($language);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Theme_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = ThemeAdminType::class;
		$entity = new Theme();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(Theme::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$surTheme = $em->getRepository(SurTheme::class)->findOneBy(["internationalName" => $entityToCopy->getSurTheme()->getInternationalName(), "language" => $language]);

		$entity->setInternationalName($entityToCopy->getInternationalName());
		
		if(!empty($surTheme))
			$entity->setSurTheme($surTheme);
		
		$entity->setLanguage($language);
		$entity->setPhoto($entityToCopy->getPhoto());

		$request->setLocale($language->getAbbreviation());

		$twig = 'index/ThemeAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit']);
    }
}