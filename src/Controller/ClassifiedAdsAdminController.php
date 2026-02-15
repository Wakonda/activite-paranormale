<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\ClassifiedAds;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\ClassifiedAdsCategory;
use App\Entity\FileManagement;
use App\Form\Type\ClassifiedAdsAdminType;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;
use App\Service\APImgSize;

#[Route('/admin/classifiedads')]
class ClassifiedAdsAdminController extends AdminGenericController
{
	protected $entityName = 'ClassifiedAds';
	protected $className = ClassifiedAds::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "ClassifiedAds_Admin_Index"; 
	protected $showRoute = "ClassifiedAds_Admin_Show";
	protected $formName = 'ap_classifiedads_admintype';

	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/index/{state}/{display}', name: 'ClassifiedAds_Admin_Index', defaults: ['state' => null, "display" => 1])]
    public function index()
    {
		$twig = 'classifiedAds/ClassifiedAdsAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'ClassifiedAds_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'classifiedAds/ClassifiedAdsAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'ClassifiedAds_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = ClassifiedAdsAdminType::class;
		$entity = new ClassifiedAds();

		$twig = 'classifiedAds/ClassifiedAdsAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'ClassifiedAds_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = ClassifiedAdsAdminType::class;
		$entity = new ClassifiedAds();

		$twig = 'classifiedAds/ClassifiedAdsAdmin/new.html.twig';

		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'ClassifiedAds_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = ClassifiedAdsAdminType::class;

		$twig = 'classifiedAds/ClassifiedAdsAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'ClassifiedAds_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = ClassifiedAdsAdminType::class;

		$twig = 'classifiedAds/ClassifiedAdsAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'ClassifiedAds_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$votes = $em->getRepository("\App\Entity\ClassifiedAdsVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'ClassifiedAds_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";
			
			$row[] =  $entity->getId();
			$row[] =  $entity->getTitle();
			$row[] =  $entity->getCategory()->getTitle();
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => $entity->getState()->getInternationalName(), 'language' => $language]);
			$row[] =  $state->getTitle();
			$row[] = !empty($entity->getLanguage()) ? '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">' : null;
			$row[] = "
			 <a href='".$this->generateUrl('ClassifiedAds_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('ClassifiedAds_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>
			";
			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	/* FONCTION DE COMPTAGE */
	public function countByState(EntityManagerInterface $em, $state)
	{
		$countByStateAdmin = $em->getRepository($this->className)->countByStateAdmin($state);
		return new Response($countByStateAdmin);
	}

	#[Route('/reloadlistsbylanguage', name: 'ClassifiedAds_Admin_ReloadListsByLanguage')]
	public function reloadListsByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language)) {
			$categories = $em->getRepository(ClassifiedAdsCategory::class)->getClassifiedAdsCategoriesByLanguage($language);
			$currentLanguagesWebsite = $em->getRepository(Language::class)->getAllAvailableLanguages(true);

			if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite)) {
				$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => 'en']);
			}

			$states = $em->getRepository(State::class)->findByLanguage($language, ['title' => 'ASC']);
		} else {
			$categories = $em->getRepository(ClassifiedAdsCategory::class)->getClassifiedAdsCategoriesByLanguage(null);
			$states = $em->getRepository(State::class)->findAll();
		}

		$categoryArray = [];
		$stateArray = [];
		
		foreach($categories as $category)
			$categoryArray[] = ["id" => $category["id"], "title" => $category["title"]];

		$translateArray['category'] = $categoryArray;

		foreach($states as $state)
			$stateArray[] = ["id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName()];

		$translateArray['state'] = $stateArray;
		
		$response = new Response(json_encode($translateArray));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	#[Route('/change_state/{id}/{state}', name: 'ClassifiedAds_Admin_ReloadListsByLanguage')]
	public function changeState(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $id, $state)
	{
		$language = $request->getLocale();

		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);

		$entity = $em->getRepository(ClassifiedAds::class)->find($id);
		
		$entity->setState($state);

		if($state->getInternationalName() == "Validate") {
			if(empty($entity->getTheme()))
				return $this->redirect($this->generateUrl('ClassifiedAds_Admin_Edit', ['id' => $id]));
		}

		$em->persist($entity);
		$em->flush();

		if($state->getInternationalName() == "Validate")
			$this->addFlash('success', $translator->trans('news.admin.NewsPublished', [], 'validators'));
		else
			$this->addFlash('success', $translator->trans('news.admin.NewsRefused', [], 'validators'));

		return $this->redirect($this->generateUrl('ClassifiedAds_Admin_Show', ['id' => $id]));
	}

	#[Route('/delete_multiple', name: 'ClassifiedAds_Admin_DeleteMultiple')]
	public function deleteMultiple(Request $request, EntityManagerInterface $em)
	{
		$ids = json_decode($request->request->get("ids"));

		$entities = $em->getRepository($this->className)->findBy(['id' => $ids]);

		foreach($entities as $entity)
			$em->remove($entity);

		$em->flush();

		return new Response();
	}
}