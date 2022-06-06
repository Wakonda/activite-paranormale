<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use App\Entity\EventMessage;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\State;
use App\Entity\User;
use App\Form\Type\EventMessageUserParticipationType;
use App\Service\APImgSize;
use App\Service\APDate;

class EventMessageController extends AbstractController
{
    public function sliderAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entities = $em->getRepository(EventMessage::class)->getLastEventsToDisplayIndex($request->getLocale());

        return $this->render('page/EventMessage/slider.html.twig', array('entities' => $entities));
    }

    public function readAction(Request $request, $id, $title_slug)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(EventMessage::class)->find($id);

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

        return $this->render('page/EventMessage/read.html.twig', array('entity' => $entity));
    }
	
	public function calendarAction()
	{
		return $this->render('page/EventMessage/calendar.html.twig');
	}
	
	public function calendarLoadEventsAction(Request $request)
	{
		$startDate = new \DateTime($request->query->get("start"));
		$endDate = new \DateTime($request->query->get("end"));

		$em = $this->getDoctrine()->getManager();
		$entities = $em->getRepository(EventMessage::class)->getAllEventsBetweenTwoDates($request->getLocale(), $startDate, $endDate);
		
		$res = [];

		foreach($entities as $entity)
		{
			$res[] = [
				"title" => $entity->getTitle(),
				"url" => $this->generateUrl('EventMessage_Read', ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]),
				"start" => $entity->getDateFrom()->format("Y-m-d"),
				"end" => $entity->getDateTo()->format("Y-m-d")
			];
		}

		$response = new Response(json_encode($res));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function tabAction(Request $request, $id, $theme)
	{
		return $this->render('page/EventMessage/tab.html.twig', array(
			'themeDisplay' => $theme,
			'themeId' => $id
		));	
	}

	public function tabDatatablesAction(Request $request, APImgSize $imgSize, APDate $date, $themeId)
	{
		$em = $this->getDoctrine()->getManager();

		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');
		$language = $request->getLocale();

		$sortByColumn = array();
		$sortDirColumn = array();
			
		for($i = 0; $i < intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}
		
        $entities = $em->getRepository(EventMessage::class)->getTab($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(EventMessage::class)->getTab($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => array()
		);

		foreach($entities as $entity)
		{
			$dateString = null;

			if($entity->getDateTo() == $entity->getDateFrom())
				$dateString = $date->doDate($request->getLocale(), $entity->getDateFrom());
			else
				$dateString = $date->doDate($request->getLocale(), $entity->getDateFrom())." - ".$date->doDate($request->getLocale(), $entity->getDateTo());
			
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
			$row = array();
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $dateString;

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	// USER PARTICIPATION
    public function newAction(Request $request)
    {
		$securityUser = $this->container->get('security.authorization_checker');
        $entity = new EventMessage();
		
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(EventMessageUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);

        return $this->render('page/EventMessage/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }
	
	public function createAction(Request $request)
    {
		return $this->genericCreateUpdate($request);
    }

	public function waitingAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(EventMessage::class)->find($id);
		if($entity->getState()->getDisplayState() == 1)
			return $this->redirect($this->generateUrl('EventMessage_Read', array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())));

		return $this->render('page/EventMessage/waiting.html.twig', array(
            'entity' => $entity,
        ));
	}

    public function editAction(Request $request, $id)
    {
		$securityUser = $this->container->get('security.authorization_checker');
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

        $entity = $em->getRepository(EventMessage::class)->find($id);
		
		if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
			throw new \Exception("You are not authorized to edit this document.");

        $form = $this->createForm(EventMessageUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);

        return $this->render('page/EventMessage/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

	public function updateAction(Request $request, $id)
    {
		return $this->genericCreateUpdate($request, $id);
    }

	public function validateAction(Request $request, $id)
	{
		$em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(EventMessage::class)->find($id);
		
		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$securityUser = $this->container->get('security.authorization_checker');

		if($entity->getState()->isStateDisplayed() or (!empty($entity->getAuthor()) and !$securityUser->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and $user->getId() != $entity->getAuthor()->getId()))
			throw new AccessDeniedHttpException("You are not authorized to edit this document.");
		
		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));
		$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));
		
		$entity->setState($state);
		$em->persist($entity);
		$em->flush();
		
		return $this->render('page/EventMessage/validate_externaluser_text.html.twig');
	}

	private function genericCreateUpdate(Request $request, $id = 0)
	{
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$securityUser = $this->container->get('security.authorization_checker');
		$em = $this->getDoctrine()->getManager();
		
		if(empty($id))
			$entity = new EventMessage();
		else
		{
			$entity = $em->getRepository(EventMessage::class)->find($id);
			
			if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
				throw new \Exception("You are not authorized to edit this document.");

		}

        $form = $this->createForm(EventMessageUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);
        $form->handleRequest($request);
		
		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));

		$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));
		
		$entity->setState($state);
		$entity->setLanguage($language);

		if(is_object($user))
		{
			if($entity->getIsAnonymous() == 1)
			{
				if($form->get('validate')->isClicked())
					$user = $em->getRepository(User::class)->findOneBy(array('username' => 'Anonymous'));
				
				$entity->setAuthor($user);
				$entity->setPseudoUsed("Anonymous");
			}
			else
			{
				$entity->setAuthor($user);
				$entity->setPseudoUsed($user->getUsername());
			}
		}
		else
		{
			$user = $em->getRepository(User::class)->findOneBy(array('username' => 'Anonymous'));
			$entity->setAuthor($user);
			$entity->setIsAnonymous(0);
		}

        if ($form->isValid())
		{	
			$em->persist($entity);
			$em->flush();
			
			return $this->redirect($this->generateUrl('EventMessage_Validate', array('id' => $entity->getId())));
        }

        return $this->render('page/EventMessage/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
	}

	// Event of the world
	public function worldAction($language, $themeId, $theme)
	{
		$em = $this->getDoctrine()->getManager();
		$flags = $em->getRepository(Language::class)->displayFlagWithoutWorld();
		$currentLanguage = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $language));

		$themes = $em->getRepository(Theme::class)->getAllThemesWorld(explode(",", $_ENV["LANGUAGES"]));

		$theme = $em->getRepository(Theme::class)->find($themeId);

		$title = [];

		if(!empty($currentLanguage))
			$title[] = $currentLanguage->getTitle();

		if(!empty($theme))
			$title[] = $theme->getTitle();

		return $this->render('page/EventMessage/world.html.twig', array(
			'flags' => $flags,
			'themes' => $themes,
			'title' => implode(" - ", $title),
			'theme' => empty($theme) ? null : $theme
		));
	}

	public function selectThemeForIndexWorldAction(Request $request, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');

		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(Theme::class)->find($themeId);
		return new Response($this->generateUrl('EventMessage_World', array('language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle())));
	}

	public function worldDatatablesAction(Request $request, APImgSize $imgSize, APDate $date, $language)
	{
		$em = $this->getDoctrine()->getManager();
		$themeId = $request->query->get("theme_id");
		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');

		$sortByColumn = array();
		$sortDirColumn = array();
			
		for($i=0 ; $i<intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}
		
        $entities = $em->getRepository(EventMessage::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(EventMessage::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => array()
		);

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
			$row = array();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
}