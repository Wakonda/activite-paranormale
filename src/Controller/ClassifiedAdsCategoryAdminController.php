<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\ClassifiedAdsCategory;
use App\Entity\Language;
use App\Service\ConstraintControllerValidator;
use App\Form\Type\ClassifiedAdsCategoryAdminType;

/**
 * ClassifiedAdsCategory controller
 *
 */
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

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = ClassifiedAdsCategoryAdminType::class;
		$entity = new ClassifiedAdsCategory();

		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = ClassifiedAdsCategoryAdminType::class;
		$entity = new ClassifiedAdsCategory();

		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = ClassifiedAdsCategoryAdminType::class;

		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = ClassifiedAdsCategoryAdminType::class;
		
		$twig = 'classifiedads/ClassifiedAdsCategoryAdmin/edit.html.twig';
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

	public function reloadListsByLanguageAction(Request $request, EntityManagerInterface $em)
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