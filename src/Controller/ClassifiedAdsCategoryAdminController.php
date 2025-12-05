<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\ClassifiedAdsCategory;
use App\Entity\Language;
use App\Service\ConstraintControllerValidator;
use App\Form\Type\ClassifiedAdsCategoryAdminType;

#[Route('/admin/classifiedadscategory')]
class ClassifiedAdsCategoryAdminController extends AdminGenericController
{
	protected $entityName = 'ClassifiedAdsCategory';
	protected $className = ClassifiedAdsCategory::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "ClassifiedAdsCategory_Admin_Index"; 
	protected $showRoute = "ClassifiedAdsCategory_Admin_Show";
	protected $formName = "ap_classifiedadscategory_admintype";

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

	#[Route('/', name: 'ClassifiedAdsCategory_Admin_Index')]
    public function index()
    {
		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'ClassifiedAdsCategory_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'ClassifiedAdsCategory_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = ClassifiedAdsCategoryAdminType::class;
		$entity = new ClassifiedAdsCategory();

		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'ClassifiedAdsCategory_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = ClassifiedAdsCategoryAdminType::class;
		$entity = new ClassifiedAdsCategory();

		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'ClassifiedAdsCategory_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = ClassifiedAdsCategoryAdminType::class;

		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'ClassifiedAdsCategory_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = ClassifiedAdsCategoryAdminType::class;
		
		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'ClassifiedAdsCategory_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'ClassifiedAdsCategory_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			
			$row[] = $entity->getTitle();
			$row[] = $entity->getParentCategory()->getTitle();
			
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Quotation_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Quotation_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/reloadlistsbylanguage', name: 'ClassifiedAdsCategory_Admin_ReloadListsByLanguage', methods: ['GET'])]
	public function reloadListsByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language))
			$parentCategories = $em->getRepository(ClassifiedAdsCategory::class)->findBy(["language" => $language, "parentCategory" => null]);
		else
			$parentCategories = $em->getRepository(ClassifiedAdsCategory::class)->findBy(["parentCategory" => null]);

		$parentCategoryArray = [];

		foreach($parentCategories as $parentCategory)
			$parentCategoryArray[] = ["id" => $parentCategory->getId(), "title" => $parentCategory->getTitle()];

		$translateArray['parentCategory'] = $parentCategoryArray;

		return new JsonResponse($translateArray);
	}
}