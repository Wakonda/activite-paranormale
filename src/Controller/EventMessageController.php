<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\EventMessage;
use App\Entity\Biography;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\State;
use App\Entity\User;
use App\Entity\Quotation;
use App\Entity\FileManagement;
use App\Entity\EntityLinkBiography;
use App\Form\Type\EventMessageUserParticipationType;
use App\Service\APImgSize;
use App\Service\APDate;

class EventMessageController extends AbstractController
{
    public function sliderAction(Request $request, EntityManagerInterface $em)
    {
		$entities = $em->getRepository(EventMessage::class)->getLastEventsToDisplayIndex($request->getLocale());

        return $this->render('page/EventMessage/slider.html.twig', ['entities' => $entities]);
    }

    public function readAction(Request $request, EntityManagerInterface $em, $id, $title_slug)
    {
		$entity = $em->getRepository(EventMessage::class)->find($id);

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

        return $this->render('page/EventMessage/read.html.twig', ['entity' => $entity]);
    }
	
	private function checkDateBetween($checkDate, $lowerBound, $upperBound) {
		$checkDate = date_create(date("Y")."-".$checkDate->format("m-d"));
		$lowerBound = date_create(date("Y")."-".$lowerBound->format("m-d"));
		$upperBound = date_create(date("Y")."-".$upperBound->format("m-d"));

		$between = false;

		if ($lowerBound < $upperBound) {
			$between = $lowerBound <= $checkDate && $checkDate <= $upperBound;
		} else {
			$between = $checkDate <= $upperBound || $checkDate >= $lowerBound;
		}

		return $between;
	}

	public function calendarAction()
	{
		return $this->render('page/EventMessage/calendar.html.twig');
	}

	public function calendarLoadEventsAction(Request $request, EntityManagerInterface $em)
	{
		$startDate = new \DateTime($request->query->get("start"));
		$endDate = new \DateTime($request->query->get("end"));

		$entities = $em->getRepository(Biography::class)->getAllEventsByDayAndMonthBetween($startDate, $endDate, $request->getLocale());

		$eventDates = [];
		$eventNumber = [];
		foreach($entities as $entity) {
			if(!empty($dt = $entity->getBirthDate())) {
				if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $dt)) {
					$dt = new \DateTime($dt);
					
					if($this->checkDateBetween($dt, $startDate, $endDate)) {
						$eventDates[$dt->format("Y")][] = $dt->format("m-d");
						
						if(!isset($eventNumber[$dt->format("Y")][$dt->format("m-d")]))
							$eventNumber[$dt->format("Y")][$dt->format("m-d")] = 0;
						$eventNumber[$dt->format("Y")][$dt->format("m-d")]++;
					}
				}
			}

			if(!empty($dt = $entity->getDeathDate())) {
				if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $dt)) {
					$dt = new \DateTime($dt);

					if($this->checkDateBetween($dt, $startDate, $endDate)) {
						$eventDates[$dt->format("Y")][] = $dt->format("m-d");
						
						if(!isset($eventNumber[$dt->format("Y")][$dt->format("Y-m-d")]))
							$eventNumber[$dt->format("Y")][$dt->format("m-d")] = 0;
						$eventNumber[$dt->format("Y")][$dt->format("m-d")]++;
					}
				}
			}
		}

		$entities = $em->getRepository(EventMessage::class)->getAllEventsBetweenTwoDates($request->getLocale(), $startDate, $endDate);

		foreach($entities as $entity)
		{
			if(empty($entity->getDateToString())) {
				$dt = new \DateTime($entity->getDateFromString());
				if($this->checkDateBetween($dt, $startDate, $endDate)) {
					$eventDates[$entity->getYearFrom()][] = $dt->format("m-d");

					if(!isset($eventNumber[$dt->format("Y")][$dt->format("m-d")]))
						$eventNumber[$entity->getYearFrom()][$dt->format("m-d")] = 0;
					$eventNumber[$entity->getYearFrom()][$dt->format("m-d")]++;
				}
			}
			else {
				$interval = \DateInterval::createFromDateString("1 day");

				$period = new \DatePeriod(new \DateTime($entity->getDateFromString()), $interval, (new \DateTime($entity->getDateToString()))->modify("+1 day"));

				foreach($period as $dt) {
					if($this->checkDateBetween($dt, $startDate, $endDate)) {
						$eventDates[$entity->getYearFrom()][] = $dt->format("m-d");

						if(!isset($eventNumber[$dt->format("Y")][$dt->format("m-d")]))
							$eventNumber[$entity->getYearFrom()][$dt->format("m-d")] = 0;
						$eventNumber[$entity->getYearFrom()][$dt->format("m-d")]++;
					}
				}
			}
		}

		$interval = \DateInterval::createFromDateString("1 day");
		$period = new \DatePeriod($startDate, $interval, $endDate);

		$res = [];

		foreach($period as $dt) {
			$numberCurrent = 0;
			$number = 0;

			if(isset($eventNumber[$dt->format("Y")][$dt->format("m-d")])) {
				$numberCurrent = $eventNumber[$dt->format("Y")][$dt->format("m-d")];
				unset($eventNumber[$dt->format("Y")][$dt->format("m-d")]);
			}
			if(!empty($eventNumber))
				$number = array_sum(array_values(array_map(function($a) use($dt) { return isset($a[$dt->format("m-d")]) ? $a[$dt->format("m-d")] : 0; }, $eventNumber)));

			$color = $numberCurrent > 0 ? "darkgreen" : ($number > 0 ? "chocolate" : "darkred");
			$res[] = [
				"title" => '<span style="color: '.$color.'">'.($numberCurrent + $number)."</span>",
				"color" => $color,
				"url" => $this->generateUrl('EventMessage_SelectDayMonth', ['year' => $dt->format("Y"), 'month' => $dt->format("m"), 'day' => $dt->format("d")]),
				"start" => $dt->format("Y-m-d"),
				"end" => $dt->format("Y-m-d")
			];
		}

		$response = new Response(json_encode($res));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function tabAction(Request $request, $id, $theme)
	{
		return $this->render('page/EventMessage/tab.html.twig', [
			'themeDisplay' => $theme,
			'themeId' => $id
		]);	
	}

	public function tabDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $themeId)
	{
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];
		$language = $request->getLocale();

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(EventMessage::class)->getTab($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(EventMessage::class)->getTab($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity) {
			$img = empty($entity->getPhotoIllustrationFilename()) ? null : $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename();
			$img = $imgSize->adaptImageSize(150, $img);

			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$img[2].'" alt="" style="width: '.$img[0].'; height:'.$img[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	// USER PARTICIPATION
    public function newAction(Request $request)
    {
        $entity = new EventMessage();

        $form = $this->createForm(EventMessageUserParticipationType::class, $entity, ["language" => $request->getLocale()]);

        return $this->render('page/EventMessage/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView()
        ]);
    }
	
	public function createAction(Request $request, EntityManagerInterface $em)
    {
		return $this->genericCreateUpdate($request, $em);
    }

	public function waitingAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(EventMessage::class)->find($id);
		if($entity->getState()->getDisplayState() == 1)
			return $this->redirect($this->generateUrl('EventMessage_Read', ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]));

		return $this->render('page/EventMessage/waiting.html.twig', [
            'entity' => $entity,
        ]);
	}

    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$user = $this->getUser();

        $entity = $em->getRepository(EventMessage::class)->find($id);

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
			throw new \Exception("You are not authorized to edit this document.");

        $form = $this->createForm(EventMessageUserParticipationType::class, $entity, ["language" => $request->getLocale()]);

        return $this->render('page/EventMessage/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView()
        ]);
    }

	public function updateAction(Request $request, EntityManagerInterface $em, $id)
    {
		return $this->genericCreateUpdate($request, $em, $id);
    }

	public function validateAction(Request $request, EntityManagerInterface $em, $id)
	{
        $entity = $em->getRepository(EventMessage::class)->find($id);

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		$user = $this->getUser();

		if($entity->getState()->isStateDisplayed() or (!empty($entity->getAuthor()) and !$this->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and !empty($user) and $user->getId() != $entity->getAuthor()->getId()))
			throw new AccessDeniedHttpException("You are not authorized to edit this document.");

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);
		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);

		$entity->setState($state);
		$em->persist($entity);
		$em->flush();

		return $this->render('page/EventMessage/validate_externaluser_text.html.twig');
	}

	private function genericCreateUpdate(Request $request, EntityManagerInterface $em, $id = 0)
	{
		$locale = $request->getLocale();
		$user = $this->getUser();

		if(empty($id))
			$entity = new EventMessage();
		else {
			$entity = $em->getRepository(EventMessage::class)->find($id);

			if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
				throw new \Exception("You are not authorized to edit this document.");
		}

        $form = $this->createForm(EventMessageUserParticipationType::class, $entity, ["language" => $locale]);
        $form->handleRequest($request);

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $locale]);
		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);

		$entity->setState($state);
		$entity->setLanguage($language);

		$entity->setType(EventMessage::EVENT_TYPE);

		if(is_object($user)) {
			if($entity->getIsAnonymous() == 1) {
				if($form->get('validate')->isClicked())
					$user = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);

				$entity->setAuthor($user);
				$entity->setPseudoUsed("Anonymous");
			}
			else {
				$entity->setAuthor($user);
				$entity->setPseudoUsed($user->getUsername());
			}
		}
		else {
			$user = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);
			$entity->setAuthor($user);
			$entity->setIsAnonymous(0);
		}

        if ($form->isValid()) {
			$generator = new \Ausi\SlugGenerator\SlugGenerator;
			$entity->setInternationalName($generator->generate($entity->getTitle()).uniqid());

			if(is_object($ci = $entity->getIllustration())) {
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
			
			return $this->redirect($this->generateUrl('EventMessage_Validate', ['id' => $entity->getId()]));
        }

        return $this->render('page/EventMessage/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView()
        ]);
	}

	// Event of the world
	public function worldAction(EntityManagerInterface $em, $language, $themeId, $theme)
	{
		$flags = $em->getRepository(Language::class)->displayFlagWithoutWorld();
		$currentLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $language]);

		$themes = $em->getRepository(Theme::class)->getAllThemesWorld(explode(",", $_ENV["LANGUAGES"]));
		$theme = $em->getRepository(Theme::class)->find($themeId);

		$title = [];

		if(!empty($currentLanguage))
			$title[] = $currentLanguage->getTitle();

		if(!empty($theme))
			$title[] = $theme->getTitle();

		return $this->render('page/EventMessage/world.html.twig', [
			'flags' => $flags,
			'themes' => $themes,
			'title' => implode(" - ", $title),
			'theme' => empty($theme) ? null : $theme
		]);
	}

	public function selectThemeForIndexWorldAction(Request $request, EntityManagerInterface $em, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');

		$theme = $em->getRepository(Theme::class)->find($themeId);
		return new Response($this->generateUrl('EventMessage_World', ['language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
	}

	public function worldDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $language)
	{
		$themeId = $request->query->get("theme_id");
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(EventMessage::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(EventMessage::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity) {
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function getAllEventsByDayAndMonthAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $apDate, $year, $month, $day)
	{
		$day = str_pad($day, 2, "0", STR_PAD_LEFT);
		$month = str_pad($month, 2, "0", STR_PAD_LEFT);
		$yearForDateTime = $year < 0 ? "-".str_pad(abs($year), 4, "0", STR_PAD_LEFT) : $year;

		$res = [];
		$currentEvent = [];

		$currentDate = new \DateTime($yearForDateTime."-".$month."-".$day);

		$bc = $translator->trans("eventMessage.dayMonth.BC", [], "validators");

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
					"theme" => !empty($entity->getTheme()) ? $entity->getTheme()->getTitle() : null,
					"url" => $this->generateUrl("EventMessage_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug() ])
				];
			}
		}

		$entities = $em->getRepository(Biography::class)->getAllEventsByDayAndMonth($day, $month, $request->getLocale());

		foreach($entities as $entity) {
			$type = EventMessage::DEATH_DATE_TYPE;
			$isBC = false;

			if(!empty($entity->getBirthDate())) {
				$isBC = str_starts_with($entity->getBirthDate(), "-");
				$yearEvent = ($isBC ? "-" : "").str_pad(explode("-", ltrim($entity->getBirthDate(), "-"))[0], 4, "0", STR_PAD_LEFT);

				$dateMonthDay = explode("-", ltrim($entity->getBirthDate(), "-"), 2);
				$date = $yearEvent.((!empty($dateMonthDay) and isset($dateMonthDay[1])) ? "-".$dateMonthDay[1] : null);

				if((new \DateTime($date))->format("m-d") == $month."-".$day)
					$type = EventMessage::BIRTH_DATE_TYPE;
			} else {
				$isBC = str_starts_with($entity->getDeathDate(), "-");
				$yearEvent = (str_starts_with($entity->getDeathDate(), "-") ? "-" : "").str_pad(explode("-", ltrim($entity->getDeathDate(), "-"))[0], 4, "0", STR_PAD_LEFT);
			}

			$get = "get".ucfirst($type);

			$romanNumber = $this->romanNumerals($this->getCentury(abs($yearEvent)));
			$centuryText = $translator->trans('eventMessage.dayMonth.Century', ["number" => $year, "romanNumber" => $romanNumber, "bc" => $bc], 'validators');

			if($yearEvent != $year) {
				$res[$type][($isBC ? "-" : "").$centuryText][$apDate->removeZero($yearEvent)][] = [
					"title" => $entity->getTitle(),
					"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title_slug" => $entity->getSlug() ])
				];
			} else {
				$currentEvent[$type][] = [
					"title" => $entity->getTitle(),
					"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title_slug" => $entity->getSlug() ])
				];
			}

			if(!empty($entity->getFeastDay())) {
				$res[EntityLinkBiography::SAINT_OCCUPATION][""][""][] = [
					"title" => $entity->getTitle(),
					"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title_slug" => $entity->getSlug() ])
				];
			}
		}

		$previous = (clone $currentDate)->modify("-1 day");
		$next = (clone $currentDate)->modify("+1 day");
		
		$nextPrevious = [
			"previous" => ["date" => $previous, "url" => $this->generateUrl("EventMessage_SelectDayMonth", ["year" => $previous->format("Y"), "month" => $previous->format("m"), "day" => $previous->format("d")])],
			"next" => ["date" => $next, "url" => $this->generateUrl("EventMessage_SelectDayMonth", ["year" => $next->format("Y"), "month" => $next->format("m"), "day" => $next->format("d")])]
		];

		$entities = $em->getRepository(Quotation::class)->getSayingsByDateAndLocale($month, $day, $request->getLocale());

		foreach($entities as $entity) {
			$res[Quotation::SAYING_FAMILY][""][""][] = [
				"title" => '<img src="'.$request->getBasePath().'/'.$entity->getCountry()->getAssetImagePath().$entity->getCountry()->getFlag().'" alt="" width="20px" height="13px"> <i>'.$entity->getTextQuotation().'</i>'
			];
		}

		return $this->render("page/EventMessage/dayMonthEvent.html.twig", [
			"res" => $res,
			"currentEvent" => $currentEvent,
			"currentDate" => $currentDate,
			"nextPrevious" => $nextPrevious
		]);
	}
	
	public function widget(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $apDate)
	{
		$year = date("Y");
		$day = date("d");
		$month = date("m");

		$currentDate = new \DateTime($year."-".$month."-".$day);

		$bc = $translator->trans("eventMessage.dayMonth.BC", [], "validators");

		$res = [];
		$currentEvent = [];
		$photos = [];

		$entities = $em->getRepository(EventMessage::class)->getAllEventsByDayAndMonth($day, $month, $request->getLocale());

		foreach($entities as $entity) {
			$yearEvent = $entity->getYearFrom();
			$romanNumber = $this->romanNumerals($this->getCentury(abs($yearEvent)));
			$centuryText = $translator->trans('eventMessage.dayMonth.Century', ["number" => $year, "romanNumber" => $romanNumber, "bc" => $bc], 'validators');

			if($yearEvent != $year) {
				$res[$entity->getType()][empty($yearEvent) ? "noYear" : $centuryText][$entity->getYearFrom()][] = [
					"id" => $entity->getId(), 
					"title" => $entity->getTitle(),
					"theme" => $entity->getTheme()->getTitle(),
					"url" => $this->generateUrl("EventMessage_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug() ]),
					"endDate" => ($entity->getDayFrom() == $entity->getDayTo() or empty($entity->getDayTo())) ? null : ["year" => $entity->getYearTo(), "month" => $entity->getMonthTo(), "day" => $entity->getDayTo()]
				];

				$photos[] = ["id" => $entity->getId(), "path" => $entity->getAssetImagePath(), "illustration" => $entity->getIllustration()];
			} else {
				$currentEvent[$entity->getType()][] = [
					"id" => $entity->getId(),
					"title" => $entity->getTitle(),
					"theme" => !empty($t = $entity->getTheme()) ? $t->getTitle() : null,
					"url" => $this->generateUrl("EventMessage_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug() ]),
					"endDate" => ($entity->getDayFrom() == $entity->getDayTo() or empty($entity->getDayTo())) ? null : ["year" => $entity->getYearTo(), "month" => $entity->getMonthTo(), "day" => $entity->getDayTo()]
				];

				$photos[] = ["id" => $entity->getId(), "path" => $entity->getAssetImagePath(), "illustration" => $entity->getIllustration()];
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
					"id" => $entity->getId(), 
					"title" => $entity->getTitle(),
					"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title_slug" => $entity->getSlug() ])
				];

				$photos[] = ["id" => $entity->getId(), "path" => $entity->getAssetImagePath(), "illustration" => $entity->getIllustration()];
			} else {
				$currentEvent[$type][] = [
					"id" => $entity->getId(), 
					"title" => $entity->getTitle(),
					"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title_slug" => $entity->getSlug() ])
				];

				$photos[] = ["id" => $entity->getId(), "path" => $entity->getAssetImagePath(), "illustration" => $entity->getIllustration()];
			}
		}
		
		$saints = $em->getRepository(Biography::class)->getAllEventsByFeastDay($month."-".$day, $request->getLocale());

		foreach($saints as $entity) {
			if(!empty($entity->getFeastDay())) {
				$res[EntityLinkBiography::SAINT_OCCUPATION][""][""][] = [
					"title" => $entity->getTitle(),
					"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title_slug" => $entity->getSlug() ])
				];
			}
		}

		foreach($photos as $key => $photo) {
			if(empty($photo["illustration"]) or !file_exists($photo["path"].$photo["illustration"]->getRealNameFile()))
				unset($photos[$key]);
		}

		$entities = $em->getRepository(Quotation::class)->getSayingsByDateAndLocale($month, $day, $request->getLocale());

		foreach($entities as $entity) {
			$res[Quotation::SAYING_FAMILY][""][""][] = [
				"title" => '<img src="'.$request->getBasePath().'/'.$entity->getCountry()->getAssetImagePath().$entity->getCountry()->getFlag().'" alt="" width="20px" height="13px"> <i>'.$entity->getTextQuotation().'</i>'
			];
		}

		return $this->render("page/EventMessage/widget.html.twig", [
			"res" => $res,
			"currentEvent" => $currentEvent,
			"currentDate" => $apDate->doDate($request->getLocale(), new \DateTime(), true),
			"illustration" => !empty($photos) ? $photos[array_rand(array_filter($photos))] : null
		]);
	}

	public function getAllEventsByYearOrMonthAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $year, $month)
	{
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

			$endDateArray = !empty($d = $entity->getDeathDate()) ? explode("-", $d) : [];
			$endArray = ["year" => null, "month" => null, "day" => null];

			if(isset($endDateArray[0]))
				$endArray["year"] = $endDateArray[0];

			if(isset($endDateArray[1]))
				$endArray["month"] = $endDateArray[1];

			if(isset($endDateArray[2]))
				$endArray["day"] = $endDateArray[2];

			$res[$type][] = [
				"title" => $entity->getTitle(),
				"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title_slug" => $entity->getSlug() ]),
				"startDate" => (empty($startArray["year"]) and empty($startArray["month"]) and empty($startArray["day"])) ? null : $startArray,
				"endDate" => (empty($endArray["year"]) and empty($endArray["month"]) and empty($endArray["day"])) ? null : $endArray
			];
		}

		return $this->render("page/EventMessage/yearMonthEvent.html.twig", [
			"res" => $res,
			"currentDate" => $currentDate
		]);
	}
	
	public function getAllEventsByYearAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $year)
	{
		$currentDate = new \DateTime($year."-01-01");

		$res = [];
		$currentEvent = [];

		$entities = $em->getRepository(EventMessage::class)->getAllEventsByMonthOrYear($year, null, $request->getLocale());

		foreach($entities as $entity) {
			$res[$entity->getType()][] = [
				"title" => $entity->getTitle(),
				"theme" => !empty($t = $entity->getTheme()) ? $t->getTitle() : null,
				"url" => $this->generateUrl("EventMessage_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug()]),
				"startDate" => ["year" => $entity->getYearFrom(), "month" => $entity->getMonthFrom(), "day" => $entity->getDayFrom()],
				"endDate" => ($entity->getDayFrom() == $entity->getDayTo() or (empty($entity->getYearTo()) or empty($entity->getMonthTo()) or empty($entity->getDayTo()))) ? null : ["year" => $entity->getYearTo(), "month" => $entity->getMonthTo(), "day" => $entity->getDayTo()]
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
				"url" => $this->generateUrl("Biography_Show", ["id" => $entity->getId(), "title_slug" => $entity->getSlug()]),
				"startDate" => (empty($startArray["year"]) and empty($startArray["month"]) and empty($startArray["day"])) ? null : $startArray,
				"endDate" => (empty($endArray["year"]) and empty($endArray["month"]) and empty($endArray["day"])) ? null : $endArray
			];
		}

		return $this->render("page/EventMessage/yearEvent.html.twig", [
			"res" => $res,
			"currentDate" => $currentDate
		]);
	}

	private function romanNumerals($num) { 
		$n = intval($num);
		$res = '';

		$roman_numerals = [
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
			'I'  => 1];

		foreach ($roman_numerals as $roman => $number) {
			$matches = intval($n / $number);
			$res .= str_repeat($roman, $matches);
			$n = $n % $number;
		}

		return $res;
	}

	function getCentury($year) 
	{
		return ceil($year / 100);
	}
}