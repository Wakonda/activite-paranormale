<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Deal;
use App\Entity\language;
use App\Form\Type\DealAdminType;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/deal')]
class DealAdminController extends AdminGenericController
{
	protected $entityName = 'Deal';
	protected $className = Deal::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Deal_Admin_Index"; 
	protected $showRoute = "Deal_Admin_Show";
	protected $illustrations = [["field" => "photo", 'selectorFile' => 'photo_selector']];

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

	#[Route('/', name: 'Deal_Admin_Index')]
    public function index()
    {
		$twig = 'deal/DealAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Deal_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'deal/DealAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'Deal_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = DealAdminType::class;
		$entity = new Deal();

		$twig = 'deal/DealAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType);
    }

	#[Route('/create', name: 'Deal_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = DealAdminType::class;
		$entity = new Deal();

		$twig = 'deal/DealAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }

	#[Route('/{id}/edit', name: 'Deal_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$formType = DealAdminType::class;

		$twig = 'deal/DealAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
    }

	#[Route('/{id}/update', name: 'Deal_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = DealAdminType::class;

		$twig = 'deal/DealAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType);
    }

	#[Route('/{id}/delete', name: 'Deal_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'Deal_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getAssetImagePath().$entity->getPhoto().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Deal_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Deal_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/internationalization/{id}', name: 'Deal_Admin_Internationalization')]
	public function internationalization(Request $request, EntityManagerInterface $em, $id)
	{
		$formType = DealAdminType::class;
		$entity = new Deal();

		$entityToCopy = $em->getRepository(Deal::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$currentLanguagesWebsite = explode(",", $_ENV["LANGUAGES"]);

		$entity->setTitle($entityToCopy->getTitle());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setLink($entityToCopy->getLink());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'deal/DealAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'new']);
	}
}