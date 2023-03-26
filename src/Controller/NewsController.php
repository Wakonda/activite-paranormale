<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Form\Type\NewsType;
use App\Entity\News;
use App\Entity\Theme;
use App\Entity\SurTheme;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\User;
use App\Entity\Licence;
use App\Entity\FileManagement;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use App\Form\Type\NewsUserParticipationType;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;

class NewsController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page, $theme)
    {
		$em = $this->getDoctrine()->getManager();
		$lang = $request->getLocale();

		$themes = $em->getRepository(Theme::class)->getTheme($lang);

		$page = (empty(intval($page))) ? 1 : $page;

		$theme = (empty($em->getRepository(Theme::class)->findOneBy(["title" => $theme]))) ? null : $theme;
		
		$params = (!empty($theme)) ? ["theme" => $theme] : [];
		$query = $em->getRepository(News::class)->getNews($theme, $lang);
		
		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('news/News/index.html.twig', array(
			'pagination' => $pagination,
			'page' => $page,
			'themes' => $themes
		));
    }
	
	public function readNewsAction(Request $request, $id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(News::class)->findByDisplayState($id);

		if(empty($entity))
			throw new NotFoundHttpException();

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(News::class)->getPreviousAndNextEntities($entity, $request->getLocale());

		return $this->render('news/News/readNews.html.twig', array(
			'entity' => $entity,
			'previousAndNextEntities' => $previousAndNextEntities
		));
	}

	public function selectThemeForIndexNewAction(Request $request)
	{
		$themeId = $request->request->get('theme_news');

		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(Theme::class)->find($themeId);
		return new Response($this->generateUrl('News_Index', array('page' => 1, 'theme' => $theme->getTitle())));
	}

	/* FONCTION DE COMPTAGE */
	public function countWorldNewsAction()
	{
		$em = $this->getDoctrine()->getManager();
		$countWorldNews = $em->getRepository(News::class)->countWorldNews();

		return new Response($countWorldNews);
	}
	/* FIN FONCTION DE COMPTAGE */
	
	// News of the world
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

		return $this->render('news/News/world.html.twig', array(
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
		return new Response($this->generateUrl('News_World', array('language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle())));
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
		
        $entities = $em->getRepository(News::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(News::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];
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
	
	// INDEX
	public function sliderAction()
	{
		$em = $this->getDoctrine()->getManager();
		$sliderNews = $em->getRepository(News::class)->getSliderNew();
		return $this->render("news/Widget/mainSlider.html.twig", array(
			"worldNews" => $sliderNews
		));
	}	
	
	public function mainSliderAction($lang)
	{
		$em = $this->getDoctrine()->getManager();
		$sliderNew = $em->getRepository(News::class)->getMainSliderNew($lang);
		return $this->render("news/Widget/jsSlider.html.twig", array(
			"sliderNews" => $sliderNew
		));
	}
	
	// ENREGISTREMENT PDF
	public function pdfVersionAction(APHtml2Pdf $html2pdf, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(News::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The article does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("news/News/pdfVersion.html.twig", array("entity" => $entity));

		return $html2pdf->generatePdf($content->getContent(), array('title' => $entity->getTitle(), 'author' => $entity->getPseudoUsed()));
	}
	
	// USER PARTICIPATION
    public function newAction(Request $request)
    {
		$securityUser = $this->container->get('security.authorization_checker');
        $entity = new News();

		$entity->setLicence($this->getDoctrine()->getManager()->getRepository(Licence::class)->getOneLicenceByLanguageAndInternationalName($request->getLocale(), "CC-BY-NC-ND 3.0"));
		
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(NewsUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);

        return $this->render('news/News/new.html.twig', array(
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
		
		$entity = $em->getRepository(News::class)->find($id);
		if($entity->getState()->getDisplayState() == 1)
			return $this->redirect($this->generateUrl('News_ReadNews_New', array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())));

		return $this->render('news/News/waiting.html.twig', array(
            'entity' => $entity,
        ));
	}

	public function validateAction(Request $request, $id)
	{
		$em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(News::class)->find($id);
		
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
		
		return $this->render('news/News/validate_externaluser_text.html.twig');
	}

    public function editAction(Request $request, $id)
    {
		$securityUser = $this->container->get('security.authorization_checker');
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		
		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

        $entity = $em->getRepository(News::class)->find($id);
		
		if($entity->getState()->getDisplayState() or $user->getId() != $entity->getAuthor()->getId())
			throw new \Exception("You are not authorized to edit this article.");

        $form = $this->createForm(NewsUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);

        return $this->render('news/News/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

	public function updateAction(Request $request, $id)
    {
		return $this->genericCreateUpdate($request, $id);
    }
	
	private function genericCreateUpdate(Request $request, $id = 0)
	{
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$securityUser = $this->container->get('security.authorization_checker');
		$em = $this->getDoctrine()->getManager();
		
		if(empty($id))
			$entity = new News();
		else
		{
			$entity = $em->getRepository(News::class)->find($id);
			
			if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
				throw new \Exception("You are not authorized to edit this document.");

		}

        $form = $this->createForm(NewsUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);
        $form->handleRequest($request);
		
		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));

		if($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('draft')->isClicked())
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Draft', 'language' => $language));
		elseif($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('preview')->isClicked())
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Draft', 'language' => $language));
		else
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
			
			if($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('preview')->isClicked())
			{
				return $this->redirect($this->generateUrl('News_Waiting', array('id' => $entity->getId())));
			}
			elseif($securityUser->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('draft')->isClicked())
			{
				return $this->redirect($this->generateUrl('Profile_Show'));
			}
			
			return $this->redirect($this->generateUrl('News_Validate', array('id' => $entity->getId())));
        }

        return $this->render('news/News/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
	}
	
	public function getSameTopicsAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(News::class)->find($id);
		$sameTopics = $em->getRepository(News::class)->getSameTopics($entity);
		
		return $this->render("news/News/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}
}