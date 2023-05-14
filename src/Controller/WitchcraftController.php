<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Grimoire;
use App\Entity\MenuGrimoire;
use App\Entity\SurThemeGrimoire;
use App\Entity\WitchcraftTool;
use App\Entity\WitchcraftThemeTool;
use App\Entity\Language;
use App\Entity\User;
use App\Entity\State;
use App\Form\Type\GrimoireUserParticipationType;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;
use Knp\Component\Pager\PaginatorInterface;

use App\Form\Type\WitchcraftToolSearchType;

class WitchcraftController extends AbstractController
{
    public function indexAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();

		$menuGrimoire = $em->getRepository(MenuGrimoire::class)->getSurThemeGrimoire($request->getLocale());
		$surThemeGrimoire = $em->getRepository(SurThemeGrimoire::class)->getSurThemeByLanguage($request->getLocale());
        
		return $this->render('witchcraft/Witchcraft/index.html.twig', [
			'surThemeGrimoires' => $surThemeGrimoire,
			'menuGrimoire' => $menuGrimoire
		]);
    }    
	
	public function tabGrimoireAction(Request $request, $theme, $id, $surtheme)
    {
		$em = $this->getDoctrine()->getManager();
		$fob = $em->getRepository(SurThemeGrimoire::class)->recupTheme($request->getLocale(), $id);
		
        return $this->render('witchcraft/Witchcraft/tabGrimoire.html.twig', [
			'themeId' => $id,
			'themeDisplay' => $theme,
			"fob" => $fob
		]);
    }

	public function tabGrimoireDatatablesAction(Request $request, APImgSize $imgSize, $themeId)
	{
		$em = $this->getDoctrine()->getManager();

		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');
		$language = $request->getLocale();

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i = 0; $i < intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}
		
        $entities = $em->getRepository(Grimoire::class)->getTabGrimoire($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Grimoire::class)->getTabGrimoire($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$img = empty($entity->getPhotoIllustrationFilename()) ? null : $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename();
			$img = $imgSize->adaptImageSize(150, $img);

			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$img[2].'" alt="" style="width: '.$img[0].';">';			
			$row[] = '<a href="'.$this->generateUrl("Witchcraft_ReadGrimoire", ['id' => $entity->getId(), 'title_slug' => $entity->getTitle(), 'surtheme' => $entity->getSurTheme()->getTitle()]).'" >'.$entity->getTitle().'</a>';

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function readGrimoireAction(Request $request, $id, $surtheme)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Grimoire::class)->findByDisplayState($id);

		if(empty($entity))
			throw new NotFoundHttpException();

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(Grimoire::class)->getPreviousAndNextEntities($entity, $request->getLocale());

        return $this->render('witchcraft/Witchcraft/readGrimoire.html.twig', [
			'previousAndNextEntities' => $previousAndNextEntities,
			'entity' => $entity
		]);
    }
	
	public function readGrimoireSimpleAction(Request $request, $id)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Grimoire::class)->findByDisplayState($id);

		if(empty($entity))
			throw new NotFoundHttpException();

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(Grimoire::class)->getPreviousAndNextEntities($entity, $request->getLocale());

        return $this->render('witchcraft/Witchcraft/readGrimoire.html.twig', [
			'previousAndNextEntities' => $previousAndNextEntities,
			'entity' => $entity
		]);
    }

	// INDEX
	public function widgetAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Grimoire::class)->getRandom($request->getLocale());

		return $this->render("witchcraft/Witchcraft/widget.html.twig", [
			"entity" => $entity
		]);
	}
	
	/********* Début fonction de comptage *********/
	public function countRitualAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$nbrRitual = $em->getRepository(Grimoire::class)->countEntree($id);
		return new Response($nbrRitual);
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(APHtml2Pdf $html2pdf, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Grimoire::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The article does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("witchcraft/Witchcraft/pdfVersion.html.twig", ["entity" => $entity]);

		return $html2pdf->generatePdf($content->getContent());
	}

	// USER PARTICIPATION
    public function newAction(Request $request)
    {
		$securityUser = $this->container->get('security.authorization_checker');
        $entity = new Grimoire();

		$user = $this->container->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(GrimoireUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);

        return $this->render('witchcraft/Witchcraft/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	public function createAction(Request $request)
    {
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$securityUser = $this->container->get('security.authorization_checker');
		$em = $this->getDoctrine()->getManager();

		$entity = new Grimoire();

        $form = $this->createForm(GrimoireUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);
        $form->handleRequest($request);

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);
		$entity->setLanguage($language);

		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);
		$entity->setState($state);

		if(is_object($user))
		{
			if($entity->getIsAnonymous() == 1)
			{
				if($form->get('validate')->isClicked())
					$user = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);
				
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
			$user = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);
			$entity->setAuthor($user);
			$entity->setIsAnonymous(0);
		}

        if ($form->isValid())
		{
			$em->persist($entity);
			$em->flush();

			return $this->redirect($this->generateUrl('Witchcraft_Validate', ["id" => $entity->getId()]));
        }

        return $this->render('witchcraft/Witchcraft/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	public function waitingAction($id)
	{
		$em = $this->getDoctrine()->getManager();

		$entity = $em->getRepository(Grimoire::class)->find($id);
		if($entity->getState()->getDisplayState() == 1)
			return $this->redirect($this->generateUrl('Witchcraft_ReadGrimoire', ['id' => $entity->getId(), 'title_slug' => $entity->getTitle(), 'surtheme' => $entity->getSurTheme()->getTitle()]));

		return $this->render('witchcraft/Witchcraft/waiting.html.twig', [
            'entity' => $entity,
        ]);
	}

	public function validateAction(Request $request, $id)
	{
		$em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(Grimoire::class)->find($id);
		
		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$securityUser = $this->container->get('security.authorization_checker');

		if($entity->getState()->isStateDisplayed() or (!empty($entity->getAuthor()) and !$securityUser->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and $user->getId() != $entity->getAuthor()->getId()))
			throw new AccessDeniedHttpException("You are not authorized to edit this document.");
		
		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);
		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);

		$entity->setState($state);
		$em->persist($entity);
		$em->flush();

		return $this->render('witchcraft/Witchcraft/validate_externaluser_text.html.twig');
	}

	public function indexWitchcraftToolAction(Request $request, PaginatorInterface $paginator, $page)
	{
		$em = $this->getDoctrine()->getManager();

		$form = $this->createForm(WitchcraftToolSearchType::class, null, ["locale" => $request->getLocale()]);
		$form->handleRequest($request);
		$datas = [];

		if ($form->isSubmitted() && $form->isValid()) {
			$datas = $form->getData();
		} else if(!empty($themeId = $request->query->get("themeId"))) {
			$datas = ["witchcraftThemeTool" => $em->getRepository(WitchcraftTool::class)->find($themeId)];
		}

		$query = $em->getRepository(WitchcraftTool::class)->getWitchcraftTools($datas, $request->getLocale());
		$themes = $em->getRepository(WitchcraftThemeTool::class)->getThemeByLanguage($request->getLocale());

		$pagination = $paginator->paginate(
			$query,
			$page,
			12
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('witchcraft/WitchcraftTool/indexWitchcraft.html.twig', ['pagination' => $pagination, 'form' => $form->createView(), "themes" => $themes]);
	}

	public function showWitchcraftToolAction($id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(WitchcraftTool::class)->find($id);
		
		return $this->render('witchcraft/WitchcraftTool/showWitchcraft.html.twig', ['entity' => $entity]);
	}

	public function worldAction($language, $themeId, $theme)
	{
		$em = $this->getDoctrine()->getManager();
		$flags = $em->getRepository(Language::class)->displayFlagWithoutWorld();
		$currentLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $language]);

		$themes = $em->getRepository(SurThemeGrimoire::class)->getAllThemesWorld(explode(",", $_ENV["LANGUAGES"]));

		$theme = $em->getRepository(SurThemeGrimoire::class)->find($themeId);

		$title = [];

		if(!empty($currentLanguage))
			$title[] = $currentLanguage->getTitle();

		if(!empty($theme))
			$title[] = $theme->getTitle();

		return $this->render('witchcraft/Witchcraft/world.html.twig', [
			'flags' => $flags,
			'themes' => $themes,
			'title' => implode(" - ", $title),
			'theme' => empty($theme) ? null : $theme
		]);
	}

	public function worldDatatablesAction(Request $request, APImgSize $imgSize, APDate $date, $language)
	{
		$em = $this->getDoctrine()->getManager();
		$themeId = $request->query->get("theme_id");
		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}

        $entities = $em->getRepository(Grimoire::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Grimoire::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title_slug' => $entity->getTitle()]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function selectThemeForIndexWorldAction(Request $request, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');

		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(SurThemeGrimoire::class)->find($themeId);
		return new Response($this->generateUrl('Witchcraft_World', ['language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
	}
}