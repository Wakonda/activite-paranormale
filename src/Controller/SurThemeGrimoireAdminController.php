<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\SurThemeGrimoire;
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
		$twig = 'witchcraft/SurThemeGrimoireAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'witchcraft/SurThemeGrimoireAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = SurThemeGrimoireAdminType::class;
		$entity = new SurThemeGrimoire();

		$twig = 'witchcraft/SurThemeGrimoireAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = SurThemeGrimoireAdminType::class;
		$entity = new SurThemeGrimoire();

		$twig = 'witchcraft/SurThemeGrimoireAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = SurThemeGrimoireAdminType::class;

		$twig = 'witchcraft/SurThemeGrimoireAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $request->getLocale()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = SurThemeGrimoireAdminType::class;
		$twig = 'witchcraft/SurThemeGrimoireAdmin/edit.html.twig';

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
			$row[] = !empty($parentTheme = $entity->getParentTheme()) ? $parentTheme->getTitle()." (".$entity->getTheme().")" : $entity->getTheme();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getAssetImagePath().$entity->getPhoto().'" alt="" width="100px">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('SurThemeGrimoire_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('SurThemeGrimoire_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
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
			$menuGrimoires = $em->getRepository(SurThemeGrimoire::class)->getParentThemeByLanguageForList($language->getAbbreviation(), $request->getLocale());
		else
			$menuGrimoires = $em->getRepository(SurThemeGrimoire::class)->getParentThemeByLanguageForList(null, $request->getLocale());

		$menuGrimoireArray = [];

		foreach($menuGrimoires as $menuGrimoire)
		{
			$menuGrimoireArray[] = array("id" => $menuGrimoire->getId(), "title" => $menuGrimoire->getTitle());
		}
		$translateArray['menuGrimoire'] = $menuGrimoireArray;

		return new JsonResponse($translateArray);
	}

	protected function defaultValueForMappedSuperclassBase(Request $request, EntityManagerInterface $em, $entity)
	{
		$language = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $request->getLocale()));
		$entity->setLanguage($language);
	}
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = SurThemeGrimoireAdminType::class;
		$entity = new SurThemeGrimoire();

		$entityToCopy = $em->getRepository(SurThemeGrimoire::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$menuGrimoire = $em->getRepository(MenuGrimoire::class)->findOneBy(["internationalName" => $entityToCopy->getParentTheme()->getInternationalName(), "language" => $language]);

		if(!empty($menuGrimoire))
			$entity->setParentTheme($menuGrimoire);

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'witchcraft/SurThemeGrimoireAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('SurThemeGrimoire_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}
}