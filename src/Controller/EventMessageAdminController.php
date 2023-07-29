<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

/**
 * EventMessage controller.
 *
 */
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

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new EventMessageTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'page/EventMessageAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'page/EventMessageAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = EventMessageAdminType::class;
		$entity = new EventMessage();

		$twig = 'page/EventMessageAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = EventMessageAdminType::class;
		$entity = new EventMessage();

		$twig = 'page/EventMessageAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = EventMessageAdminType::class;

		$twig = 'page/EventMessageAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = EventMessageAdminType::class;
		
		$twig = 'page/EventMessageAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository("\App\Entity\EventMessageComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\EventMessageVote")->findBy(["eventMessage" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($em, $id);
    }
	
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
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
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('EventMessage_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('EventMessage_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

    public function indexStateAction(Request $request, $state)
    {
        $em = $this->getDoctrine()->getManager();
		
        $entities = $em->getRepository($this->className)->getByStateAdmin($state);
		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($request->getLocale(), $state);
		
        return $this->render('page/EventMessageAdmin/indexState.html.twig', array(
            'entities' => $entities,
			'state' => $state
        ));
    }

	public function changeStateAction(Request $request, $id, $state)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $request->getLocale();
		
		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);

		$entity = $em->getRepository($this->className)->find($id);
		
		$entity->setState($state);
		$em->persist($entity);
		$em->flush();

		$formType = EventMessageAdminType::class;

		$twig = 'page/EventMessageAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('EventMessage_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}

	public function countByStateAction($state)
	{
		$em = $this->getDoctrine()->getManager();
		$countByStateAdmin = $em->getRepository($this->className)->countByStateAdmin($state);
		return new Response($countByStateAdmin);
	}
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = EventMessageAdminType::class;
		$entity = new EventMessage();
		
		$em = $this->getDoctrine()->getManager();
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
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	public function wikidataAction(Request $request, \App\Service\Wikidata $wikidata)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");

		$res = $wikidata->getEventDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
}