<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use App\Form\Type\NewsType;
use App\Entity\News;
use App\Entity\Theme;
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
use Doctrine\ORM\EntityManagerInterface;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;

class NewsController extends AbstractController
{
	#[Route('/news/{page}/{theme}', name: 'News_Index', defaults: ['theme' => null], requirements: ['page' => '\d+', 'theme' => '.+'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page, $theme)
    {
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

		return $this->render('news/News/index.html.twig', [
			'pagination' => $pagination,
			'page' => $page,
			'themes' => $themes
		]);
    }

	#[Route('/NewRead-{id}-{title}.ap', name: 'News_ReadNews', requirements: ['title' => '.+'])]
	#[Route('/ArchiveRead-{id}-{title}.ap', name: 'Archive_Old_ReadNews', requirements: ['title' => '.+'])]
	#[Route('/news/read/{id}/{title_slug}', name: 'News_ReadNews_New', defaults: ['title_slug' => null], requirements: ['title_slug' => '.+'])]
	public function readNews(Request $request, EntityManagerInterface $em, $id, $title_slug = null)
	{
		$entity = $em->getRepository(News::class)->findByDisplayState($id);

		if(empty($entity))
			throw new NotFoundHttpException();

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(News::class)->getPreviousAndNextEntities($entity, $request->getLocale());

		return $this->render('news/News/readNews.html.twig', [
			'entity' => $entity,
			'previousAndNextEntities' => $previousAndNextEntities
		]);
	}

	#[Route('/selectThemeForIndexNewAction', name: 'News_SelectThemeForIndexNew')]
	public function selectThemeForIndexNew(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_news');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('News_Index', ['page' => 1, 'theme' => $theme->getTitle()]));
	}
	
	// News of the world
	#[Route('/news/world/{language}/{themeId}/{theme}', name: 'News_World', defaults: ['language' => 'all', 'themeId' => 0, 'theme' => null], requirements: ['theme' => '.+'])]
	public function world(EntityManagerInterface $em, $language, $themeId, $theme)
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

		return $this->render('news/News/world.html.twig', [
			'flags' => $flags,
			'themes' => $themes,
			'title' => implode(" - ", $title),
			'theme' => empty($theme) ? null : $theme
		]);
	}

	#[Route('/news/selectThemeForIndexWorldAction/{language}', name: 'News_SelectThemeForIndexWorld', defaults: ['language' => 'all'])]
	public function selectThemeForIndexWorld(Request $request, EntityManagerInterface $em, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('News_World', ['language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
	}

	#[Route('/news/worlddatatables/{language}/{themeId}', name: 'News_WorldDatatables', defaults: ['language' => 'all', 'themeId' => 0])]
	public function worldDatatables(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $language)
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

        $entities = $em->getRepository(News::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(News::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="'.addslashes($entity->getLanguage()->getTitle()).'" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="'.addslashes($entity->getTitle()).'" style="width: '.$photo[0].';">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	// INDEX
	#[Route('/slider', name: 'News_Slider')]
	public function slider(EntityManagerInterface $em)
	{
		$sliderNews = $em->getRepository(News::class)->getSliderNew();

		return $this->render("news/Widget/mainSlider.html.twig", [
			"worldNews" => $sliderNews
		]);
	}

	#[Route('/mainslider/{lang}', name: 'News_MainSlider')]
	public function mainSlider(EntityManagerInterface $em, $lang)
	{
		$sliderNew = $em->getRepository(News::class)->getMainSliderNew($lang);

		return $this->render("news/Widget/jsSlider.html.twig", [
			"sliderNews" => $sliderNew
		]);
	}

	// ENREGISTREMENT PDF
	#[Route('/news/pdfversion/{id}', name: 'News_Pdfversion')]
	public function pdfVersion(EntityManagerInterface $em, APHtml2Pdf $html2pdf, $id)
	{
		$entity = $em->getRepository(News::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The article does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("news/News/pdfVersion.html.twig", ["entity" => $entity]);

		return $html2pdf->generatePdf($content->getContent(), ['title' => $entity->getTitle(), 'author' => $entity->getPseudoUsed()]);
	}
	
	// USER PARTICIPATION
	#[Route('/news/new', name: 'News_New')]
	#[Route('/news/published', name: 'News_User_News')]
    public function newAction(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker)
    {
        $entity = new News();

		$entity->setLicence($em->getRepository(Licence::class)->getOneLicenceByLanguageAndInternationalName($request->getLocale(), "CC-BY-NC-ND 3.0") ?? null);

        $form = $this->createForm(NewsUserParticipationType::class, $entity, ["language" => $request->getLocale(), "securityUser" => $authorizationChecker]);

        return $this->render('news/News/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	#[Route('/news/create', name: 'News_Create')]
	#[Route('/news/published/create/{draft}/{preview}', name: 'News_User_Create', defaults: ['draft' => 0, 'preview' => 0])]
	public function create(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker)
    {
		return $this->genericCreateUpdate($request, $em, $authorizationChecker);
    }

	#[Route('/news/waiting/{id}', name: 'News_Waiting')]
	public function waiting(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(News::class)->find($id);
		if($entity->getState()->getDisplayState() == 1)
			return $this->redirect($this->generateUrl('News_ReadNews_New', ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]));

		return $this->render('news/News/waiting.html.twig', [
            'entity' => $entity,
        ]);
	}

	#[Route('/news/validate/{id}', name: 'News_Validate')]
	public function validate(Request $request, EntityManagerInterface $em, $id)
	{
        $entity = $em->getRepository(News::class)->find($id);
		
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
		
		return $this->render('news/News/validate_externaluser_text.html.twig');
	}

	#[Route('/news/edit/{id}', name: 'News_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker, $id)
    {
		$user = $this->getUser();
		$entity = $em->getRepository(News::class)->find($id);

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

        $entity = $em->getRepository(News::class)->find($id);
		
		if($entity->getState()->getDisplayState() or $user->getId() != $entity->getAuthor()->getId())
			throw new \Exception("You are not authorized to edit this article.");

        $form = $this->createForm(NewsUserParticipationType::class, $entity, ["language" => $request->getLocale(), "securityUser" => $authorizationChecker]);

        return $this->render('news/News/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	#[Route('/news/update/{id}', name: 'News_Update')]
	public function update(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker, $id)
    {
		return $this->genericCreateUpdate($request, $em, $authorizationChecker, $id);
    }
	
	private function genericCreateUpdate(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker, $id = 0)
	{
		$user = $this->getUser();

		if(empty($id))
			$entity = new News();
		else
		{
			$entity = $em->getRepository(News::class)->find($id);
			
			if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
				throw new \Exception("You are not authorized to edit this document.");
		}

        $form = $this->createForm(NewsUserParticipationType::class, $entity, ["language" => $request->getLocale(), "securityUser" => $authorizationChecker]);
        $form->handleRequest($request);
		
		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		if($this->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('draft')->isClicked())
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Draft', 'language' => $language]);
		elseif($this->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('preview')->isClicked())
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Draft', 'language' => $language]);
		else
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);
		
		$entity->setState($state);
		$entity->setLanguage($language);

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

        if ($form->isValid()) {
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
			
			if($this->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('preview')->isClicked())
				return $this->redirect($this->generateUrl('News_Waiting', ['id' => $entity->getId()]));
			elseif($this->isGranted('IS_AUTHENTICATED_FULLY') and $form->get('draft')->isClicked()) {
				$this->addFlash('success', $translator->trans('news.new.DraftSuccess', [], 'validators'));
				return $this->redirect($this->generateUrl('Profile_Show'));
			}
			
			return $this->redirect($this->generateUrl('News_Validate', ['id' => $entity->getId()]));
        }

        return $this->render('news/News/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
	}
	
	public function getSameTopics(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(News::class)->find($id);
		$sameTopics = $em->getRepository(News::class)->getSameTopics($entity);
		
		return $this->render("news/News/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}
}