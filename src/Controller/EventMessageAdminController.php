<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use Doctrine\ORM\Query\ResultSetMapping;

use App\Entity\EventMessage;
use App\Entity\State;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\Licence;
use App\Entity\FileManagement;
use App\Entity\EventMessageTags;
use App\Form\Type\EventMessageAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

#[Route('/admin/eventmessage')]
class EventMessageAdminController extends AdminGenericController
{
	protected $entityName = 'EventMessage';
	protected $className = EventMessage::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "EventMessage_Admin_Index"; 
	protected $showRoute = "EventMessage_Admin_Show";
	protected $formName = "ap_page_eventmessageadmintype";
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	protected $illustrationThumbnails = [["field" => "thumbnail", "selectorFile" => "thumbnail_selector"]];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrationThumbnails);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new EventMessageTags(), $entityBindded);
	}

	#[Route('/', name: 'EventMessage_Admin_Index')]
    public function index()
    {
		$twig = 'page/EventMessageAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'EventMessage_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'page/EventMessageAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'EventMessage_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = EventMessageAdminType::class;
		$entity = new EventMessage();

		$twig = 'page/EventMessageAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'EventMessage_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = EventMessageAdminType::class;
		$entity = new EventMessage();

		$twig = 'page/EventMessageAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'EventMessage_Admin_Edit')]
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = EventMessageAdminType::class;

		$twig = 'page/EventMessageAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'EventMessage_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = EventMessageAdminType::class;
		
		$twig = 'page/EventMessageAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'EventMessage_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\EventMessageComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\EventMessageVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }

	#[Route('/archive/{id}', name: 'EventMessage_Admin_Archive', requirements: ['id' => '\d+'])]
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
	}

	#[Route('/datatables', name: 'EventMessage_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = $entity->getState()->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('EventMessage_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('EventMessage_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/change_state/{id}/{state}', name: 'EventMessage_Admin_ChangeState', requirements: ['id' => '\d+'])]
	public function changeStateAction(Request $request, EntityManagerInterface $em, $id, $state)
	{
		$language = $request->getLocale();
		
		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);
		$entity = $em->getRepository($this->className)->find($id);
		
		$entity->setState($state);

		$formType = EventMessageAdminType::class;

		$twig = 'page/EventMessageAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
	}

	#[Route('/showImageSelectorColorbox', name: 'EventMessage_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('EventMessage_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'EventMessage_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	public function countByStateAction(EntityManagerInterface $em, $state)
	{
		$countByStateAdmin = $em->getRepository($this->className)->countByStateAdmin($state);
		return new Response($countByStateAdmin);
	}

	#[Route('/internationalization/{id}', name: 'EventMessage_Admin_Internationalization')]
    public function internationalization(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = EventMessageAdminType::class;
		$entity = new EventMessage();

		$entityToCopy = $em->getRepository(EventMessage::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$entity->setLanguage($language);

		$entity->setTitle($entityToCopy->getTitle());
		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setWikidata($entityToCopy->getWikidata());
		$entity->setType($entityToCopy->getType());
		
		$entity->setDayFrom($entityToCopy->getDayFrom());
		$entity->setMonthFrom($entityToCopy->getMonthFrom());
		$entity->setYearFrom($entityToCopy->getYearFrom());
		
		$entity->setDayTo($entityToCopy->getDayTo());
		$entity->setMonthTo($entityToCopy->getMonthTo());
		$entity->setYearTo($entityToCopy->getYearTo());
		
		$entity->setSource($entityToCopy->getSource());

		$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getState()->getInternationalName()]);
		
		if(empty($state)) {
			$defaultLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
			$state = $em->getRepository(State::class)->findOneBy(["language" => $defaultLanguage, "internationalName" => "Validate"]);
		}

		$entity->setState($state);

		$licence = $em->getRepository(Licence::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getLicence()->getInternationalName()]);
		
		if(empty($licence)) {
			$defaultLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
			$licence = $em->getRepository(Licence::class)->findOneBy(["language" => $defaultLanguage, "internationalName" => $entityToCopy->getLicence()->getInternationalName()]);
		}

		$entity->setLicence($licence);

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
		
		$entity->setThumbnail($entityToCopy->getThumbnail());
		
		$theme = null;
		
		if(!empty($entityToCopy->getTheme()))
			$theme = $em->getRepository(Theme::class)->findOneBy(["internationalName" => $entityToCopy->getTheme()->getInternationalName(), "language" => $language]);
		
		$entity->setTheme($theme);
		
		$entity->setLatitude($entityToCopy->getLatitude());
		$entity->setLongitude($entityToCopy->getLongitude());

		$twig = 'page/EventMessageAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	#[Route('/wikidata', name: 'EventMessage_Admin_Wikidata')]
	public function wikidata(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");

		$res = $wikidata->getEventDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}

	#[Route('/delete_multiple', name: 'EventMessage_Admin_DeleteMultiple')]
	public function deleteMultiple(Request $request, EntityManagerInterface $em)
	{
		$ids = json_decode($request->request->get("ids"));

		$entities = $em->getRepository($this->className)->findBy(['id' => $ids]);

		foreach($entities as $entity)
			$em->remove($entity);

		$em->flush();

		return new Response();
	}

	#[Route('/reload_theme_by_language', name: 'EventMessage_Admin_ReloadThemeByLanguage')]
	public function reloadThemeByLanguageAction(Request $request, EntityManagerInterface $em)
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
			$countries = $em->getRepository(Region::class)->getCountryByLanguage($language->getAbbreviation())->getQuery()->getResult();
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList(null, $request->getLocale());
			$states = $em->getRepository(State::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
			$countries = $em->getRepository(Region::class)->findAll();
		}

		$themeArray = [];
		$stateArray = [];
		$licenceArray = [];
		$countryArray = [];
		
		foreach($themes as $theme)
			$themeArray[] = ["id" => $theme["id"], "title" => $theme["title"]];

		$translateArray['theme'] = $themeArray;

		foreach($states as $state)
			$stateArray[] = ["id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName()];

		$translateArray['state'] = $stateArray;

		foreach($licences as $licence)
			$licenceArray[] = ["id" => $licence->getId(), "title" => $licence->getTitle()];

		$translateArray['licence'] = $licenceArray;

		foreach($countries as $country)
			$countryArray[] = ["id" => $country->getInternationalName(), "title" => $country->getTitle()];

		$translateArray['country'] = $countryArray;
		
		$response = new Response(json_encode($translateArray));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
}