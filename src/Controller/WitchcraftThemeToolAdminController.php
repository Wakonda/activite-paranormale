<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\WitchcraftThemeTool;
use App\Entity\Language;
use App\Form\Type\WitchcraftThemeToolAdminType;
use App\Service\ConstraintControllerValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/witchcraftthemetool')]
class WitchcraftThemeToolAdminController extends AdminGenericController
{
	protected $entityName = 'WitchcraftThemeTool';
	protected $className = WitchcraftThemeTool::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "WitchcraftThemeTool_Admin_Index"; 
	protected $showRoute = "WitchcraftThemeTool_Admin_Show";
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

	#[Route('/', name: 'WitchcraftThemeTool_Admin_Index')]
    public function index()
    {
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/index.html.twig';//rCFebs985£
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'WitchcraftThemeTool_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'WitchcraftThemeTool_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType);
    }

	#[Route('/create', name: 'WitchcraftThemeTool_Admin_Create', requirements: ['_method' => "post"])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }

	#[Route('/{id}/edit', name: 'WitchcraftThemeTool_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
    }

	#[Route('/{id}/update', name: 'WitchcraftThemeTool_Admin_Update', requirements: ['_method' => "post"])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType);
    }

	#[Route('/{id}/delete', name: 'WitchcraftThemeTool_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'WitchcraftThemeTool_Admin_IndexDatatables', requirements: ['_method' => "get"])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('WitchcraftThemeTool_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('WitchcraftThemeTool_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/internationalization/{id}', name: 'WitchcraftThemeTool_Admin_Internationalization')]
    public function internationalization(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();

		$entityToCopy = $em->getRepository(WitchcraftThemeTool::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }
}