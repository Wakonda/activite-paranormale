<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Cartography;
use App\Entity\CartographyTags;
use App\Entity\State;
use App\Entity\Licence;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\FileManagement;
use App\Form\Type\CartographyAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

/**
 * Cartography controller.
 *
 */
class CartographyAdminController extends AdminGenericController
{
	protected $entityName = 'Cartography';
	protected $className = Cartography::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Cartography_Admin_Index"; 
	protected $showRoute = "Cartography_Admin_Show";
	protected $formName = 'ap_cartography_cartographyadmintype';
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new CartographyTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'cartography/CartographyAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'cartography/CartographyAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = CartographyAdminType::class;
		$entity = new Cartography();

		$twig = 'cartography/CartographyAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = CartographyAdminType::class;
		$entity = new Cartography();

		$twig = 'cartography/CartographyAdmin/new.html.twig';

		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Cartography::class)->find($id);
		$formType = CartographyAdminType::class;

		$twig = 'cartography/CartographyAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = CartographyAdminType::class;
		
		$twig = 'cartography/CartographyAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\CartographyComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\CartographyTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }
	
	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Cartography_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Cartography_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
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
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList($language->getAbbreviation(), $request->getLocale());
			
			$currentLanguagesWebsite = explode(",", $_ENV["LANGUAGES"]);
			if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite))
				$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => 'en']);

			$states = $em->getRepository(State::class)->findByLanguage($language, ['title' => 'ASC']);
			$licences = $em->getRepository(Licence::class)->findByLanguage($language, ['title' => 'ASC']);
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList(null, $request->getLocale());
			$states = $em->getRepository(State::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
		}

		$themeArray = [];
		$stateArray = [];
		$licenceArray = [];
		
		foreach($themes as $theme)
			$themeArray[] = ["id" => $theme["id"], "title" => $theme["title"]];

		$translateArray['theme'] = $themeArray;

		foreach($states as $state)
		{
			$stateArray[] = ["id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName()];
		}
		$translateArray['state'] = $stateArray;

		foreach($licences as $licence)
		{
			$licenceArray[] = ["id" => $licence->getId(), "title" => $licence->getTitle()];
		}
		$translateArray['licence'] = $licenceArray;

		return new JsonResponse($translateArray);
	}
	
	public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
	{
		$formType = CartographyAdminType::class;
		$entity = new Cartography();

		$entityToCopy = $em->getRepository(Cartography::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$theme = $em->getRepository(Theme::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getTheme()->getInternationalName()]);
		$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getState()->getInternationalName()]);
		
		if(empty($state)) {
			$defaultLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
			$state = $em->getRepository(State::class)->findOneBy(["language" => $defaultLanguage, "internationalName" => "Validate"]);
		}

		$entity->setState($state);

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
		
		if(!empty($theme))
			$entity->setTheme($theme);
		$entity->setLanguage($language);
		$entity->setCoordXMap($entityToCopy->getCoordXMap());
		$entity->setCoordYMap($entityToCopy->getCoordYMap());
		$entity->setLinkGMaps($entityToCopy->getLinkGMaps());
		$entity->setWikidata($entityToCopy->getWikidata());
		$entity->setSource($entityToCopy->getSource());

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

		$request->setLocale($language->getAbbreviation());

		$twig = 'cartography/CartographyAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGeneric('Cartography_Admin_LoadImageSelectorColorbox');
	}

	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}
	
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
	}
}