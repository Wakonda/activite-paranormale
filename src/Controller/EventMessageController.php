<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\EventMessage;
use App\Entity\Biography;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\State;
use App\Entity\User;
use App\Entity\FileManagement;
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
		
		$eventDates = [];

		foreach($entities as $entity)
		{
			$interval = \DateInterval::createFromDateString("1 day");
			$period = new \DatePeriod(new \DateTime($entity->getDateFrom()), $interval, (new \DateTime($entity->getDateTo()))->modify("+1 day"));
			
			foreach($period as $dt) {
				$eventDates[] = $dt->format("Y-m-d");
			}
		}

		$interval = \DateInterval::createFromDateString("1 day");
		$period = new \DatePeriod($startDate, $interval, $endDate);
		
		$res = [];
		
		foreach($period as $dt) {
			$res[] = [
				"title" => '<i class="fas fa-play-circle fa-2x"></i>',
				"color" => in_array($dt->format("Y-m-d"), $eventDates) ? 'darkgreen' : "darkred",
				"url" => $this->generateUrl('EventMessage_SelectDayMonth', ['year' => $dt->format("Y"), 'month' => $dt->format("m"), 'day' => $dt->format("d")]),
				"start" => $dt->format("Y-m-d"),
				"end" => $dt->format("Y-m-d")
			];
		}

		/*$res = [];

		foreach($entities as $entity)
		{
			$res[] = [
				"title" => $entity->getTitle(),
				"url" => $this->generateUrl('EventMessage_Read', ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]),
				"start" => $entity->getDateFrom(),
				"end" => $entity->getDateTo()
			];
		}*/

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
				$dateString = $date->doPartialDate($entity->getDateFromString(), $request->getLocale());
			else
				$dateString = $date->doPartialDate($entity->getDateFromString(), $request->getLocale())." - ".$date->doPartialDate($entity->getDateToString(), $request->getLocale());

			$img = empty($entity->getPhotoIllustrationFilename()) ? null : $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename();
			$img = $imgSize->adaptImageSize(150, $img);

			$row = array();
			$row[] = '<img src="'.$request->getBasePath().'/'.$img[2].'" alt="" style="width: '.$img[0].'; height:'.$img[1].'">';			
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
			if(is_object($ci = $entity->getIllustration()))
			{
				$titleFile = uniqid()."_".$ci->getClientOriginalName();
				$illustration = new FileManagement();
				$illustration->setTitleFile($titleFile);
				$illustration->setRealNameFile($titleFile);
				$illustration->setExtensionFile(pathinfo($ci->getClientOriginalName(), PATHINFO_EXTENSION));
				
				$ci->move($entity->getTmpUploadRootDir(), $titleFile);
				
				$entity->setIllustration($illustration);
			}

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
	
	public function getAllEventsByDayAndMonthAction(Request $request, TranslatorInterface $translator, $year, $month, $day)
	{
		$em = $this->getDoctrine()->getManager();
		
		$day = str_pad($day, 2, "0", STR_PAD_LEFT);
		$month = str_pad($month, 2, "0", STR_PAD_LEFT);

		$currentDate = new \DateTime($year."-".$month."-".$day);

		$bc = $translator->trans("eventMessage.dayMonth.BC", [], "validators");

		$res = [];
		$currentEvent = [];

		$entities = $em->getRepository(EventMessage::class)->getAllEventsByDayAndMonth($day, $month, $request->getLocale());
		
		foreach($entities as $entity) {
			$yearEvent = $entity->getYearFrom();
			$romanNumber = $this->romanNumerals($this->getCentury(abs($yearEvent)));
			$centuryText = $translator->trans('eventMessage.dayMonth.Century', ["number" => $year, "romanNumber" => $romanNumber, "bc" => $bc], 'validators');
			
			if($yearEvent != $year) {
				$res[$entity->getType()][empty($yearEvent) ? "noYear" : $centuryText][$entity->getYearFrom()][] = [
					"title" => $entity->getTitle(),
					"theme" => $entity->getTheme()->getTitle(),
					"url" => $this->generateUrl("EventMessage_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug() ])
				];
			} else {
				$currentEvent[$entity->getType()][] = [
					"title" => $entity->getTitle(),
					"theme" => $entity->getTheme()->getTitle(),
					"url" => $this->generateUrl("EventMessage_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug() ])
				];
			}
		}
		
		$entities = $em->getRepository(Biography::class)->getAllEventsByDayAndMonth($day, $month, $request->getLocale());
		
		foreach($entities as $entity) {
			$type = EventMessage::DEATH_DATE_TYPE;

			if(!empty($entity->getBirthDate()) and (new \DateTime($entity->getBirthDate()))->format("m-d") == $month."-".$day)
				$type = EventMessage::BIRTH_DATE_TYPE;
			
			$get = "get".ucfirst($type);
			
			$yearEvent = (new \DateTime($entity->$get()))->format("Y");
			$romanNumber = $this->romanNumerals($this->getCentury(abs($yearEvent)));
			$centuryText = $translator->trans('eventMessage.dayMonth.Century', ["number" => $year, "romanNumber" => $romanNumber, "bc" => $bc], 'validators');

			if($yearEvent != $year) {
				$res[$type][$centuryText][(new \DateTime($entity->$get()))->format("Y")][] = [
					"title" => $entity->getTitle(),
					"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title" => $entity->getTitle() ])
				];
			} else {
				$currentEvent[$type][] = [
					"title" => $entity->getTitle(),
					"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title" => $entity->getTitle() ])
				];
			}
		}

		return $this->render("page/EventMessage/dayMonthEvent.html.twig", [
			"res" => $res,
			"currentEvent" => $currentEvent,
			"currentDate" => $currentDate
		]);
	}
	
	public function getAllEventsByYearOrMonthAction(Request $request, TranslatorInterface $translator, $year, $month)
	{
		$em = $this->getDoctrine()->getManager();

		$month = !empty($month) ? str_pad($month, 2, "0", STR_PAD_LEFT) : "01";

		$currentDate = new \DateTime($year."-".$month."-01");

		$res = [];
		$currentEvent = [];

		$entities = $em->getRepository(EventMessage::class)->getAllEventsByMonthOrYear($year, $month, $request->getLocale());
		
		foreach($entities as $entity) {
			$res[$entity->getType()][] = [
				"title" => $entity->getTitle(),
				"theme" => $entity->getTheme()->getTitle(),
				"url" => $this->generateUrl("EventMessage_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug() ]),
				"startDate" => ["year" => $entity->getYearFrom(), "month" => $entity->getMonthFrom(), "day" => $entity->getDayFrom()],
				"endDate" => ($entity->getDayFrom() == $entity->getDayTo()) ? null : ["year" => $entity->getYearTo(), "month" => $entity->getMonthTo(), "day" => $entity->getDayTo()]
			];
		}
		
		$entities = $em->getRepository(Biography::class)->getAllEventsByMonthOrYear($year, $month, $request->getLocale());

		foreach($entities as $entity) {
			$type = EventMessage::DEATH_DATE_TYPE;

			if(!empty($entity->getBirthDate()) and (new \DateTime($entity->getBirthDate()))->format("Y-m") == $year."-".$month)
				$type = EventMessage::BIRTH_DATE_TYPE;
			
			$get = "get".ucfirst($type);
			
			$startDateArray = explode("-", $entity->getBirthDate());
			$startArray = ["year" => null, "month" => null, "day" => null];
			
			if(isset($startDateArray[0]))
				$startArray["year"] = $startDateArray[0];
			
			if(isset($startDateArray[1]))
				$startArray["month"] = $startDateArray[1];
			
			if(isset($startDateArray[2]))
				$startArray["day"] = $startDateArray[2];
			
			$endDateArray = explode("-", $entity->getDeathDate());
			$endArray = ["year" => null, "month" => null, "day" => null];
			
			if(isset($endDateArray[0]))
				$endArray["year"] = $endDateArray[0];
			
			if(isset($endDateArray[1]))
				$endArray["month"] = $endDateArray[1];
			
			if(isset($endDateArray[2]))
				$endArray["day"] = $endDateArray[2];

			$res[$type][] = [
				"title" => $entity->getTitle(),
				"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title" => $entity->getTitle() ]),
				"startDate" => (empty($startArray["year"]) and empty($startArray["month"]) and empty($startArray["day"])) ? null : $startArray,
				"endDate" => (empty($endArray["year"]) and empty($endArray["month"]) and empty($endArray["day"])) ? null : $endArray
			];
		}

		return $this->render("page/EventMessage/yearMonthEvent.html.twig", [
			"res" => $res,
			"currentDate" => $currentDate
		]);
	}
	
	public function getAllEventsByYearAction(Request $request, TranslatorInterface $translator, $year)
	{
		$em = $this->getDoctrine()->getManager();

		$currentDate = new \DateTime($year."-01-01");

		$res = [];
		$currentEvent = [];

		$entities = $em->getRepository(EventMessage::class)->getAllEventsByMonthOrYear($year, null, $request->getLocale());
		
		foreach($entities as $entity) {
			$res[$entity->getType()][] = [
				"title" => $entity->getTitle(),
				"theme" => $entity->getTheme()->getTitle(),
				"url" => $this->generateUrl("EventMessage_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug() ]),
				"startDate" => ["year" => $entity->getYearFrom(), "month" => $entity->getMonthFrom(), "day" => $entity->getDayFrom()],
				"endDate" => ($entity->getDayFrom() == $entity->getDayTo()) ? null : ["year" => $entity->getYearTo(), "month" => $entity->getMonthTo(), "day" => $entity->getDayTo()]
			];
		}
		
		$entities = $em->getRepository(Biography::class)->getAllEventsByMonthOrYear($year, null, $request->getLocale());

		foreach($entities as $entity) {
			$type = EventMessage::DEATH_DATE_TYPE;

			if(!empty($entity->getBirthDate()) and (new \DateTime($entity->getBirthDate()))->format("Y") == $year)
				$type = EventMessage::BIRTH_DATE_TYPE;
			
			$get = "get".ucfirst($type);
			
			$startDateArray = explode("-", $entity->getBirthDate());
			$startArray = ["year" => null, "month" => null, "day" => null];
			
			if(isset($startDateArray[0]))
				$startArray["year"] = $startDateArray[0];
			
			if(isset($startDateArray[1]))
				$startArray["month"] = $startDateArray[1];
			
			if(isset($startDateArray[2]))
				$startArray["day"] = $startDateArray[2];
			
			$endDateArray = explode("-", $entity->getDeathDate());
			$endArray = ["year" => null, "month" => null, "day" => null];
			
			if(isset($endDateArray[0]))
				$endArray["year"] = $endDateArray[0];
			
			if(isset($endDateArray[1]))
				$endArray["month"] = $endDateArray[1];
			
			if(isset($endDateArray[2]))
				$endArray["day"] = $endDateArray[2];

			$res[$type][] = [
				"title" => $entity->getTitle(),
				"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title" => $entity->getTitle() ]),
				"startDate" => (empty($startArray["year"]) and empty($startArray["month"]) and empty($startArray["day"])) ? null : $startArray,
				"endDate" => (empty($endArray["year"]) and empty($endArray["month"]) and empty($endArray["day"])) ? null : $endArray
			];
		}

		return $this->render("page/EventMessage/yearEvent.html.twig", [
			"res" => $res,
			"currentDate" => $currentDate
		]);
	}
	
	private function romanNumerals($num){ 
		$n = intval($num); 
		$res = ''; 

		/*** roman_numerals array  ***/ 
		$roman_numerals = array( 
			'M'  => 1000, 
			'CM' => 900, 
			'D'  => 500, 
			'CD' => 400, 
			'C'  => 100, 
			'XC' => 90, 
			'L'  => 50, 
			'XL' => 40, 
			'X'  => 10, 
			'IX' => 9, 
			'V'  => 5, 
			'IV' => 4, 
			'I'  => 1); 

		foreach ($roman_numerals as $roman => $number){ 
			/*** divide to get  matches ***/ 
			$matches = intval($n / $number); 

			/*** assign the roman char * $matches ***/ 
			$res .= str_repeat($roman, $matches); 

			/*** substract from the number ***/ 
			$n = $n % $number; 
		} 

		/*** return the res ***/ 
		return $res; 
	}

	function getCentury($year) 
	{
		return ceil($year / 100);
	}
}