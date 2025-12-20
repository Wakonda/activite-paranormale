<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\Query\ResultSetMapping;

use App\Entity\Advertising;
use App\Entity\Language;
use App\Form\Type\AdvertisingAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\APImgSize;

#[Route('/admin/advertising')]
class AdvertisingAdminController extends AdminGenericController
{
	protected $entityName = 'Advertising';
	protected $className = Advertising::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Advertising_Admin_Index"; 
	protected $showRoute = "Advertising_Admin_Show";
	protected $formName = 'ap_advertising_advertisingadmintype';

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/', name: 'Advertising_Admin_Index')]
    public function index()
    {
		$twig = 'advertising/AdvertisingAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Advertising_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'advertising/AdvertisingAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'Advertising_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = AdvertisingAdminType::class;
		$entity = new Advertising();

		$twig = 'advertising/AdvertisingAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'Advertising_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = AdvertisingAdminType::class;
		$entity = new Advertising();

		$twig = 'advertising/AdvertisingAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'Advertising_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Advertising::class)->find($id);
		$formType = AdvertisingAdminType::class;

		$twig = 'advertising/AdvertisingAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'Advertising_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = AdvertisingAdminType::class;
		
		$twig = 'advertising/AdvertisingAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'Advertising_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/wysiwyg_uploadfile', name: 'Advertising_Admin_WYSIWYG_UploadFile')]
    public function WYSIWYGUploadFile(Request $request, APImgSize $imgSize)
    {
		return $this->WYSIWYGUploadFileGeneric($request, $imgSize, new Advertising());
    }

	#[Route('/datatables', name: 'Advertising_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $entity->getWidth();
			$row[] = $entity->getHeight();
			$row[] = "
			 <a href='".$this->generateUrl('Advertising_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Advertising_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
}