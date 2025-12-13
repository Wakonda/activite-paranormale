<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\UsefullinkTags;
use App\Form\Type\UsefullinkTagsAdminType;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/usefullinktags')]
class UsefullinkTagsAdminController extends AdminGenericController {
	protected $entityName = 'UsefullinkTags';
	protected $className = UsefullinkTags::class;

	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "UsefullinkTags_Admin_Index"; 
	protected $showRoute = "UsefullinkTags_Admin_Show";
	protected $formName = 'ap_quotation_UsefullinkTagsAdminType';

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/', name: 'UsefullinkTags_Admin_Index')]
    public function index()
    {
		$twig = 'usefullink/UsefullinkTagsAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'UsefullinkTags_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'usefullink/UsefullinkTagsAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'UsefullinkTags_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = UsefullinkTagsAdminType::class;
		$entity = new UsefullinkTags();

		$twig = 'usefullink/UsefullinkTagsAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'new']);
    }

	#[Route('/create', name: 'UsefullinkTags_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = UsefullinkTagsAdminType::class;
		$entity = new UsefullinkTags();

		$twig = 'usefullink/UsefullinkTagsAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['action' => 'new']);
    }

	#[Route('/{id}/edit', name: 'UsefullinkTags_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(UsefullinkTags::class)->find($id);
		$formType = UsefullinkTagsAdminType::class;

		$twig = 'usefullink/UsefullinkTagsAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['action' => 'edit']);
    }

	#[Route('/{id}/update', name: 'UsefullinkTags_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = UsefullinkTagsAdminType::class;
		$twig = 'usefullink/UsefullinkTagsAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['action' => 'edit']);
    }

	#[Route('/{id}/delete', name: 'UsefullinkTags_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'UsefullinkTags_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('UsefullinkTags_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('UsefullinkTags_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/autocomplete', name: 'UsefullinkTags_Admin_Autocomplete')]
	public function autocomplete(Request $request, EntityManagerInterface $em)
	{
		$query = $request->query->get("q", null);
		$datas = $em->getRepository(UsefullinkTags::class)->getAutocomplete($query);

		$results = [];

		foreach($datas as $data)
		{
			$obj = new \stdClass();
			$obj->id = $data["id"];
			$obj->text = $data["title"];
			$obj->title = $data["title"];

			$results[] = $obj;
		}

        return new JsonResponse(["results" => $results]);
	}
}