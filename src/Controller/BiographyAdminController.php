<?php

namespace App\Controller;

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Biography;
use App\Entity\Language;
use App\Entity\FileManagement;
use App\Entity\Country;
use App\Form\Type\BiographyAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Biography controller.
 *
 */
class BiographyAdminController extends AdminGenericController
{
	protected $entityName = 'Biography';
	protected $className = Biography::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Biography_Admin_Index"; 
	protected $showRoute = "Biography_Admin_Show";
	protected $formName = 'ap_quotation_biographyadmintype';

	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'quotation/BiographyAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'quotation/BiographyAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = BiographyAdminType::class;
		$entity = new Biography();

		$twig = 'quotation/BiographyAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'new', 'locale' => $request->getLocale()]);
    }
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = BiographyAdminType::class;
		$entity = new Biography();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(Biography::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		
		$country = null;
		
		if(!empty($entityToCopy->getNationality()))
			$country = $em->getRepository(Country::class)->findOneBy(["internationalName" => $entityToCopy->getNationality()->getInternationalName(), "language" => $language]);
		
		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setKind($entityToCopy->getKind());
		$entity->setLanguage($language);
		$entity->setBirthDate($entityToCopy->getBirthDate());
		$entity->setDeathDate($entityToCopy->getDeathDate());
		$entity->setNationality($country);
		$entity->setLinks($entityToCopy->getLinks());
		$entity->setWikidata($entityToCopy->getWikidata());

		if(!empty($ci = $entityToCopy->getIllustration())) {
			$illustration = new FileManagement();
			$illustration->setTitleFile($ci->getTitleFile());
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());

			$entity->setIllustration($illustration);
		}

		$twig = 'quotation/BiographyAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = BiographyAdminType::class;
		$entity = new Biography();

		$twig = 'quotation/BiographyAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['action' => 'new', 'locale' =>  $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction($id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository(Biography::class)->find($id);
		$formType = BiographyAdminType::class;

		$twig = 'quotation/BiographyAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['action' => 'edit', 'locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = BiographyAdminType::class;
		$twig = 'quotation/BiographyAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['action' => 'edit', 'locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		return $this->deleteGenericAction($id);
    }

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Biography_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$em = $this->getDoctrine()->getManager();

		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);
		
		$toComplete = $request->query->get("toComplete");

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $toComplete);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $toComplete, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

		foreach($entities as $entity)
		{
			$row = array();
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Biography_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Biography_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function autocompleteAction(Request $request)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);

		if(is_numeric($locale)) {
			$language = $this->getDoctrine()->getManager()->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}
	
		$datas =  $this->getDoctrine()->getManager()->getRepository(Biography::class)->getAutocomplete($locale, $query);
		
		$results = array();
		
		foreach($datas as $data)
		{
			$obj = new \stdClass();
			$obj->id = $data["id"];
			$obj->text = $data["title"];
			$obj->internationalName = $data["internationalName"];
			$obj->title = $data["title"];
			$obj->wikidata = $data["wikidata"];
			
			$results[] = $obj;
		}

        return new JsonResponse(["results" => $results]);
	}

	public function reloadByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];

		$countryArray = array();
		
		if(!empty($language))
			$countries = $em->getRepository(Country::class)->findByLanguage($language, array('title' => 'ASC'));
		else
			$countries = $em->getRepository(Country::class)->findAll();
		
		foreach($countries as $country)
			$countryArray[] = array("id" => $country->getId(), "title" => $country->getTitle());

		$translateArray['country'] = $countryArray;

		return new JsonResponse($translateArray);
	}
	
	public function wikidataAction(Request $request, \App\Service\Wikidata $wikidata)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getBiographyDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
	
	public function validateBiographyAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$wikidata = $request->query->get("wikidata");
		$title = $request->query->get("title");
		$language = $request->query->get("language");

		$entities = $em->getRepository(Biography::class)->getBiographyByWikidataOrTitle($title, $wikidata);
		$path = (new Biography())->getAssetImagePath();

		return $this->render("quotation/BiographyAdmin/_validateBiography.html.twig", ["entities" => $entities, "path" => $path, "language" => $language, "wikidata" => $wikidata]);
	}
	
	public function quickAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $locale, $wikidata, $internationalName)
	{
		$formType = BiographyAdminType::class;
		$entity = new Biography();
		
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($locale);
		
		$entityToCopy = null;
		
		if(!empty($internationalName)) {
			$entityToCopy = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $internationalName]);
		$country = null;
		
		if(!empty($entityToCopy->getNationality()))
			$country = $em->getRepository(Country::class)->findOneBy(["internationalName" => $entityToCopy->getNationality()->getInternationalName(), "language" => $language]);

			$entity->setInternationalName($entityToCopy->getInternationalName());
			$entity->setTitle($entityToCopy->getTitle());
			$entity->setKind($entityToCopy->getKind());
			$entity->setBirthDate($entityToCopy->getBirthDate());
			$entity->setDeathDate($entityToCopy->getDeathDate());
			$entity->setNationality($country);
			$entity->setLinks($entityToCopy->getLinks());

			if(!empty($ci = $entityToCopy->getIllustration())) {
				$illustration = new FileManagement();
				$illustration->setTitleFile($ci->getTitleFile());
				$illustration->setCaption($ci->getCaption());
				$illustration->setLicense($ci->getLicense());
				$illustration->setAuthor($ci->getAuthor());
				$illustration->setUrlSource($ci->getUrlSource());

				$entity->setIllustration($illustration);
			}
		}
		
		$entity->setLanguage($language);
		$entity->setWikidata($wikidata);
		$entity->setInternationalName($internationalName);
		
		$form = $this->createForm($formType, $entity, ['action' => 'new', 'locale' => $request->getLocale()]);

		if ($request->isMethod(Request::METHOD_POST)){
			$twig = 'quotation/BiographyAdmin/quick.html.twig';
			$res = $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['action' => 'new', 'locale' =>  $this->getLanguageByDefault($request, $this->formName)]);
			
			if ("Symfony\Component\HttpFoundation\RedirectResponse" == get_class($res)) {
				$path = (new Biography())->getAssetImagePath();
				
				// $entity = $em->getRepository(Biography::class)->find(2528);
				
				
				$entities = $em->getRepository(Biography::class)->getBiographyByWikidataOrTitle($entity->getTitle(), $entity->getWikidata());
				$data = $this->render("quotation/BiographyAdmin/quick_data.html.twig", ["entity" => $entities[0], "path" => $path, "entityNew" => $entity]);
				return new JsonResponse(["state" => "success", "data" => $data->getContent()]);
			} else {
				return new JsonResponse(["state" => "failed", "data" => $res]);
			}
		}

		return $this->render("quotation/BiographyAdmin/quick.html.twig", ["form" => $form->createView(), 'locale' => $request->getLocale(), "wikidata" => $wikidata]);
	}
}