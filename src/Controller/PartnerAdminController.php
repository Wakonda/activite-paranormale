<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Partner;
use App\Form\Type\PartnerAdminType;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/partner')]
class PartnerAdminController extends AdminGenericController
{
	protected $entityName = 'Partner';
	protected $className = Partner::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Partner_Admin_Index"; 
	protected $showRoute = "Partner_Admin_Show";
	protected $illustrations = [["field" => "photo", "selectorFile" => "photo_selector"]];

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

	#[Route('/', name: 'Partner_Admin_Index')]
    public function index()
    {
		$twig = 'partner/PartnerAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Partner_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'partner/PartnerAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'Partner_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = PartnerAdminType::class;
		$entity = new Partner();

		$twig = 'partner/PartnerAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType);
    }

	#[Route('/create', name: 'Partner_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = PartnerAdminType::class;
		$entity = new Partner();

		$twig = 'partner/PartnerAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }

	#[Route('/{id}/edit', name: 'Partner_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$formType = PartnerAdminType::class;

		$twig = 'partner/PartnerAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
    }

	#[Route('/{id}/update', name: 'Partner_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = PartnerAdminType::class;
		$twig = 'partner/PartnerAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType);
    }

	#[Route('/{id}/delete', name: 'Partner_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'Partner_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<a href="'.$entity->getLink().'">'.$entity->getLink().'</a>';
			$row[] = '<img src="'.$request->getBasePath().'/extended/photo/language/'.$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Partner_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Partner_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
}