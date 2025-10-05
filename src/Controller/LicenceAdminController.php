<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Licence;
use App\Entity\Language;
use App\Form\Type\LicenceAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Licence controller.
 *
 */
class LicenceAdminController extends AdminGenericController
{
	protected $entityName = 'Licence';
	protected $className = Licence::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Licence_Admin_Index"; 
	protected $showRoute = "Licence_Admin_Show";
	protected $illustrations = [["field" => "logo", 'selectorFile' => 'logo_selector']];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'index/LicenceAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'index/LicenceAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = LicenceAdminType::class;
		$entity = new Licence();

		$twig = 'index/LicenceAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = LicenceAdminType::class;
		$entity = new Licence();

		$twig = 'index/LicenceAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(EntityManagerInterface $em, $id)
    {
		$formType = LicenceAdminType::class;

		$twig = 'index/LicenceAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = LicenceAdminType::class;
		
		$twig = 'index/LicenceAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Licence_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Licence_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = LicenceAdminType::class;
		$entity = new Licence();

		$entityToCopy = $em->getRepository(Licence::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setLink($entityToCopy->getLink());
		$entity->setLanguage($language);
		$entity->setLogo($entityToCopy->getLogo());

		$request->setLocale($language->getAbbreviation());

		$twig = 'index/LicenceAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGeneric('Licence_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}
}