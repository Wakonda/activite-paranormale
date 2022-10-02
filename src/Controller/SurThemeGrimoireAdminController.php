<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\SurThemeGrimoire;
use App\Entity\MenuGrimoire;
use App\Entity\Language;
use App\Form\Type\SurThemeGrimoireAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * surThemeGrimoire controller.
 *
 */
class SurThemeGrimoireAdminController extends AdminGenericController
{
	protected $entityName = 'SurThemeGrimoire';
	protected $className = SurThemeGrimoire::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "SurThemeGrimoire_Admin_Index"; 
	protected $showRoute = "SurThemeGrimoire_Admin_Show";
	protected $formName = "ap_witchcraft_surthemegrimoireadmintype";
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
		$twig = 'witchcraft/SurThemeGrimoireAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'witchcraft/SurThemeGrimoireAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = SurThemeGrimoireAdminType::class;
		$entity = new SurThemeGrimoire();

		$twig = 'witchcraft/SurThemeGrimoireAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = SurThemeGrimoireAdminType::class;
		$entity = new SurThemeGrimoire();

		$twig = 'witchcraft/SurThemeGrimoireAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction($id)
    {
		$formType = SurThemeGrimoireAdminType::class;

		$twig = 'witchcraft/SurThemeGrimoireAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $request->getLocale()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = SurThemeGrimoireAdminType::class;
		$twig = 'witchcraft/SurThemeGrimoireAdmin/edit.html.twig';

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
			$row[] = $entity->getMenuGrimoire()->getTitle()." (".$entity->getTheme().")";
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getAssetImagePath().$entity->getPhoto().'" alt="" width="100px">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('SurThemeGrimoire_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('SurThemeGrimoire_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
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
			$menuGrimoires = $em->getRepository(MenuGrimoire::class)->findByLanguage($language, array('title' => 'ASC'));
		else
			$menuGrimoires = $em->getRepository(MenuGrimoire::class)->findAll();

		$menuGrimoireArray = array();

		foreach($menuGrimoires as $menuGrimoire)
		{
			$menuGrimoireArray[] = array("id" => $menuGrimoire->getId(), "title" => $menuGrimoire->getTitle());
		}
		$translateArray['menuGrimoire'] = $menuGrimoireArray;

		return new JsonResponse($translateArray);
	}

	protected function defaultValueForMappedSuperclassBase(Request $request, $entity)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $request->getLocale()));
		$entity->setLanguage($language);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = SurThemeGrimoireAdminType::class;
		$entity = new SurThemeGrimoire();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(SurThemeGrimoire::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$menuGrimoire = $em->getRepository(MenuGrimoire::class)->findOneBy(["internationalName" => $entityToCopy->getMenuGrimoire()->getInternationalName(), "language" => $language]);

		if(!empty($menuGrimoire))
			$entity->setMenuGrimoire($menuGrimoire);

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'witchcraft/SurThemeGrimoireAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit']);
    }

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('SurThemeGrimoire_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}
}