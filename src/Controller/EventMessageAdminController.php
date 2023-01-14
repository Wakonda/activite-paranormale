<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
use App\Form\Type\EventMessageAdminType;
use App\Service\ConstraintControllerValidator;

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

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrationThumbnails);
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'page/EventMessageAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'page/EventMessageAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = EventMessageAdminType::class;
		$entity = new EventMessage();

		$twig = 'page/EventMessageAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = EventMessageAdminType::class;
		$entity = new EventMessage();

		$twig = 'page/EventMessageAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = EventMessageAdminType::class;

		$twig = 'page/EventMessageAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = EventMessageAdminType::class;
		
		$twig = 'page/EventMessageAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository("\App\Entity\EventMessageComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\EventMessageVote")->findBy(["eventMessage" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($id);
    }
	
	public function archiveAction($id)
	{
		return $this->archiveGenericArchive($id);
	}
	
	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = array();
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('EventMessage_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br>
			 <a href='".$this->generateUrl('EventMessage_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br>";

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
		return $this->editGenericAction($id, $twig, $formType);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('EventMessage_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}

	public function countByStateAction($state)
	{
		$em = $this->getDoctrine()->getManager();
		$countByStateAdmin = $em->getRepository($this->className)->countByStateAdmin($state);
		return new Response($countByStateAdmin);
	}
	
    public function internationalizationAction(Request $request, $id)
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
		
		$licence = null;
		
		if(!empty($entityToCopy->getLicence()))
			$licence = $em->getRepository(Licence::class)->findOneBy(["internationalName" => $entityToCopy->getLicence()->getInternationalName(), "language" => $language]);
		
		$entity->setLicence($licence);
		
		$state = null;
		
		if(!empty($entityToCopy->getState()))
			$state = $em->getRepository(State::class)->findOneBy(["internationalName" => $entityToCopy->getState()->getInternationalName(), "language" => $language]);
		
		$entity->setState($state);

		if(!empty($wikicode = $entityToCopy->getWikidata())) {
			$wikidata = new \App\Service\Wikidata($em);
			$data = $wikidata->getTitleAndUrl($wikicode, $language->getAbbreviation());
			
			if(!empty($data))
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
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
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