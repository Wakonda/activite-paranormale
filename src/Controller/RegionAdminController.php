<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Region;
use App\Entity\Language;
use App\Form\Type\RegionAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Region controller.
 *
 */
class RegionAdminController extends AdminGenericController
{
	protected $entityName = 'Region';
	protected $className = Region::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Region_Admin_Index"; 
	protected $showRoute = "Region_Admin_Show";
	protected $illustrations = [["field" => "flag", 'selectorFile' => 'photo_selector']];
	
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
		$twig = 'index/RegionAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'index/RegionAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = RegionAdminType::class;
		$entity = new Region();

		$twig = 'index/RegionAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = RegionAdminType::class;
		$entity = new Region();

		$twig = 'index/RegionAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(EntityManagerInterface $em, $id)
    {
		$formType = RegionAdminType::class;

		$twig = 'index/RegionAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = RegionAdminType::class;
		
		$twig = 'index/RegionAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType);
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
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getAssetImagePath().$entity->getFlag().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Region_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Region_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Region_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = RegionAdminType::class;
		$entity = new Region();

		$entityToCopy = $em->getRepository(Region::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setLanguage($language);
		$entity->setFlag($entityToCopy->getFlag());
		$entity->setFamily($entityToCopy->getFamily());

		$request->setLocale($language->getAbbreviation());

		$twig = 'index/RegionAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }
}