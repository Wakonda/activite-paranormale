<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Biography;
use App\Entity\Language;
use App\Entity\FileManagement;
use App\Entity\Region;
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
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		$biographies = $em->getRepository($this->className)->findBy(["internationalName" => $entityBindded->getInternationalName()]);

		if(count($biographies) == 1)
			return;

		$datas = [];
		
		foreach ($biographies as $biography) {
			if(!empty($d = $biography->getBirthDate()))
				$datas["birthDate"] = $d;
			if(!empty($d = $biography->getDeathDate()))
				$datas["deathDate"] = $d;
			if(!empty($d = $biography->getNationality()))
				$datas["nationality"] = $d;
			if(!empty($d = $biography->getWikidata()))
				$datas["wikidata"] = $d;
			if(!empty($d = $biography->getGender()))
				$datas["gender"] = $d;
			
			$illustration = !empty($entityBindded->getIllustration()) ? $entityBindded->getIllustration() : $biography->getIllustration();

			if(!empty($d = $illustration)) {
				$datas["illustration"] = [
					"titleFile" => $d->getTitleFile(),
					"realNameFile" => $d->getRealNameFile(),
					"extensionFile" => $d->getExtensionFile(),
					"kindFile" => $d->getKindFile(),
					"caption" => $d->getCaption(),
					"license" => $d->getLicense(),
					"author" => $d->getAuthor(),
					"urlSource" => $d->getUrlSource()
				];
			}

			if(!empty($biography->getLinks()))
				foreach(json_decode($biography->getLinks(), true) as $link)
					if(!empty($link["url"]))
						$datas["links"][] = ["link" => $link["link"], "url" => $link["url"], "label" => $link["label"]];

			if(!empty($biography->getIdentifiers()))
				foreach(json_decode($biography->getIdentifiers(), true) as $identifier)
					if(!empty($identifier["value"]))
						$datas["identifiers"][] = ["identifier" => $identifier["identifier"], "value" => $identifier["value"]];
		}
		
		if(isset($datas["links"]))
			$datas["links"] = array_map("unserialize", array_unique(array_map("serialize", $datas["links"])));

		if(isset($datas["identifiers"]))
			$datas["identifiers"] = array_map("unserialize", array_unique(array_map("serialize", $datas["identifiers"])));

		foreach ($biographies as $biography) {
			if(isset($datas["birthDate"]))
				$biography->setBirthDate($datas["birthDate"]);
			if(isset($datas["deathDate"]))
				$biography->setDeathDate($datas["deathDate"]);
			if(isset($datas["nationality"]))
				$biography->setNationality($datas["nationality"]);
			if(isset($datas["wikidata"]))
				$biography->setWikidata($datas["wikidata"]);
			if(isset($datas["gender"]))
				$biography->setGender($datas["gender"]);

			if(isset($datas["links"]))
				$biography->setLinks(json_encode($datas["links"]));
		
			if(isset($datas["identifiers"]))
				$biography->setIdentifiers(json_encode($datas["identifiers"]));
			
			if(!empty($datas["illustration"]) and !empty($datas["illustration"]["realNameFile"]) and empty($biography->getIllustration())) {
				$illustration = new FileManagement();
				$illustration->setTitleFile($datas["illustration"]["titleFile"]);
				$illustration->setRealNameFile($datas["illustration"]["realNameFile"]);
				$illustration->setExtensionFile($datas["illustration"]["extensionFile"]);
				$illustration->setKindFile($datas["illustration"]["kindFile"]);
				$illustration->setCaption($datas["illustration"]["caption"]);
				$illustration->setLicense($datas["illustration"]["license"]);
				$illustration->setAuthor($datas["illustration"]["author"]);
				$illustration->setUrlSource($datas["illustration"]["urlSource"]);
				$em->persist($illustration);

				$biography->setIllustration($illustration);
			}

			$em->persist($biography);
		}

		$em->flush();
	}

    public function indexAction()
    {
		$twig = 'quotation/BiographyAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'quotation/BiographyAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = BiographyAdminType::class;
		$entity = new Biography();

		$twig = 'quotation/BiographyAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'new', 'locale' => $request->getLocale()]);
    }

    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = BiographyAdminType::class;
		$entity = new Biography();

		$entityToCopy = $em->getRepository(Biography::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		
		$country = null;
		$entity->setTitle($entityToCopy->getTitle());

		if(!empty($wikicode = $entityToCopy->getWikidata())) {
			$wikidata = new \App\Service\Wikidata($em);
			$data = $wikidata->getTitleAndUrl($wikicode, $language->getAbbreviation());

			if(!empty($data) and !empty($data["url"]))
			{
				$sourceArray = [[
					"author" => null,
					"url" => $data["url"],
					"type" => "url",
				]];
				
				$entity->setSource(json_encode($sourceArray));
				
				if(!empty($title = $data["title"]))
					$entity->setTitle($title);
			}
		}
		
		if(!empty($entityToCopy->getNationality()))
			$country = $em->getRepository(Region::class)->findOneBy(["internationalName" => $entityToCopy->getNationality()->getInternationalName(), "language" => $language]);
		
		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setKind($entityToCopy->getKind());
		$entity->setLanguage($language);
		$entity->setBirthDate($entityToCopy->getBirthDate());
		$entity->setDeathDate($entityToCopy->getDeathDate());
		$entity->setNationality($country);
		$entity->setLinks($entityToCopy->getLinks());
		$entity->setWikidata($entityToCopy->getWikidata());
		$entity->setIdentifiers($entityToCopy->getIdentifiers());
		$entity->setGender($entityToCopy->getGender());

		if(!empty($ci = $entityToCopy->getIllustration())) {
			$illustration = new FileManagement();
			$illustration->setTitleFile($ci->getTitleFile());
			$illustration->setRealNameFile($ci->getRealNameFile());
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());

			$entity->setIllustration($illustration);
		}
		
		$roles = $em->getRepository(\App\Entity\EntityLinkBiography::class)->getOccupationsByBiography($entityToCopy->getId());

		$twig = 'quotation/BiographyAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation(), "default_roles" => $roles]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = BiographyAdminType::class;
		$entity = new Biography();

		$twig = 'quotation/BiographyAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['action' => 'new', 'locale' =>  $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Biography::class)->find($id);
		$formType = BiographyAdminType::class;

		$twig = 'quotation/BiographyAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['action' => 'edit', 'locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = BiographyAdminType::class;
		$twig = 'quotation/BiographyAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['action' => 'edit', 'locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGenericAction($em, $id);
    }

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Biography_Admin_LoadImageSelectorColorbox');
	}

	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);
		
		$toComplete = $request->query->get("toComplete");

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $toComplete);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $toComplete, true);

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
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Biography_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Biography_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function autocompleteAction(Request $request, EntityManagerInterface $em)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		$kinds = $request->query->all("kinds");

		if(is_numeric($locale)) {
			$language = $em->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}

		$datas =  $em->getRepository(Biography::class)->getAutocomplete($locale, $query, $kinds);

		$results = [];

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

	public function reloadByLanguageAction(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];

		$countryArray = [];
		
		if(!empty($language))
			$countries = $em->getRepository(Region::class)->findByLanguage($language, ['title' => 'ASC']);
		else
			$countries = $em->getRepository(Region::class)->findAll();
		
		foreach($countries as $country)
			$countryArray[] = ["id" => $country->getId(), "title" => $country->getTitle()];

		$translateArray['country'] = $countryArray;

		return new JsonResponse($translateArray);
	}

	public function wikidataAction(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");

		$res = $wikidata->getBiographyDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}

	public function validateBiographyAction(Request $request, EntityManagerInterface $em)
	{
		$wikidata = $request->query->get("wikidata");
		$title = $request->query->get("title");
		$language = $request->query->get("language");

		$entities = $em->getRepository(Biography::class)->getBiographyByWikidataOrTitle($title, $wikidata);
		$path = (new Biography())->getAssetImagePath();

		$internationalName = (!empty($entities) ? $entities[0]["internationalName"] : null);

		return $this->render("quotation/BiographyAdmin/_validateBiography.html.twig", ["entities" => $entities, "path" => $path, "language" => $language, "wikidata" => $wikidata, "title" => $title, "internationalName" => $internationalName]);
	}
	
	public function quickAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $locale, $title)
	{
		$wikidataCode = $request->query->get("wikidata");
		$internationalName = $request->query->get("internationalName");
		$formType = BiographyAdminType::class;
		$entity = new Biography();

		if(is_numeric($locale))
			$language = $em->getRepository(Language::class)->find($locale);
		else
			$language = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $locale]);

		$entityToCopy = null;

		if(!empty($internationalName) or !empty($wikidataCode)) {
			if(!empty($internationalName))
				$entityToCopy = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $internationalName]);
			else
				$entityToCopy = $em->getRepository(Biography::class)->findOneBy(["wikidata" => $wikidataCode]);

			if(!empty($entityToCopy)) {
				$country = null;
			
				if(!empty($entityToCopy->getNationality()))
					$country = $em->getRepository(Region::class)->findOneBy(["internationalName" => $entityToCopy->getNationality()->getInternationalName(), "language" => $language]);

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
					$illustration->setRealNameFile($ci->getRealNameFile());
					$illustration->setCaption($ci->getCaption());
					$illustration->setLicense($ci->getLicense());
					$illustration->setAuthor($ci->getAuthor());
					$illustration->setUrlSource($ci->getUrlSource());

					$entity->setIllustration($illustration);
				}
			}
		}
		
		$entity->setTitle($title);
		$entity->setLanguage($language);
		$entity->setWikidata($wikidataCode);
		$entity->setInternationalName($internationalName);
		
		$form = $this->createForm($formType, $entity, ['action' => 'new', 'locale' => $language->getAbbreviation()]);

		if ($request->isMethod(Request::METHOD_POST)) {
			$form->handleRequest($request);
			$this->validationForm($request, $em, $ccv, $translator, $form, $entity, $entity);

			if ($form->isValid()) {
				$this->uploadFile($entity, $form);
				$em->persist($entity);
				$em->flush();
				
				$path = (new Biography())->getAssetImagePath();

				$entities = $em->getRepository(Biography::class)->getBiographyByWikidataOrTitle($entity->getTitle(), $entity->getWikidata());
				$data = $this->render("quotation/BiographyAdmin/quick_data.html.twig", ["entity" => $entities[0], "path" => $path, "entityNew" => $entity]);
				return new JsonResponse(["state" => "success", "data" => $data->getContent()]);
			} else {
				$twig = 'quotation/BiographyAdmin/quick.html.twig';
				$res = $this->render($twig, ['entity' => $entity, 'form' => $form->createView(), "locale" => $request->getLocale(), "title" => $title, "wikidata" => $wikidataCode, 'internationalName' => $internationalName]);
				return new JsonResponse(["state" => "failed", "data" => $res]);
			}
		}

		return $this->render("quotation/BiographyAdmin/quick.html.twig", ["form" => $form->createView(), 'internationalName' => $internationalName, 'locale' => $language->getId(), "title" => $title, "wikidata" => $wikidataCode]);
	}
}