<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\DocumentFamily;
use App\Entity\Language;
use App\Form\Type\DocumentFamilyAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * DocumentFamily controller.
 *
 */
class DocumentFamilyAdminController extends AdminGenericController
{
	protected $entityName = 'DocumentFamily';
	protected $className = DocumentFamily::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "DocumentFamily_Admin_Index"; 
	protected $showRoute = "DocumentFamily_Admin_Show";

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
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
		$twig = 'document/DocumentFamilyAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'document/DocumentFamilyAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = DocumentFamilyAdminType::class;
		$entity = new DocumentFamily();

		$twig = 'document/DocumentFamilyAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = DocumentFamilyAdminType::class;
		$entity = new DocumentFamily();

		$twig = 'document/DocumentFamilyAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(EntityManagerInterface $em, $id)
    {
		$formType = DocumentFamilyAdminType::class;

		$twig = 'document/DocumentFamilyAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = DocumentFamilyAdminType::class;
		
		$twig = 'document/DocumentFamilyAdmin/edit.html.twig';
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
			$row[] = $entity->getInternationalName();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('DocumentFamily_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('DocumentFamily_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = DocumentFamilyAdminType::class;
		$entity = new DocumentFamily();

		$entityToCopy = $em->getRepository(DocumentFamily::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'document/DocumentFamilyAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }
}