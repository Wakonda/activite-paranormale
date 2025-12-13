<?php

namespace App\Controller\Mobile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Knp\Component\Pager\PaginatorInterface;

use App\Form\Type\SearchEngineType;
use App\Service\SearchEngine;
use App\Service\PaginatorNativeSQL;
use App\Service\FunctionsLibrary;

use App\Entity\News;
use App\Entity\Theme;
use App\Entity\Page;
use App\Entity\User;
use App\Entity\State;
use App\Entity\Licence;
use App\Entity\Language;
use App\Entity\FileManagement;
use App\Form\Type\NewsUserParticipationType;
use Detection\MobileDetect;

class NewsMobileController extends AbstractController
{
	#[Route('/mobile/index/{page}/{theme}', name: 'ap_newsmobile_index', defaults: ['page' => 1, 'theme' => null], requirements: ['page' => '\d+', 'theme' => '.+'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, FunctionsLibrary $functionsLibrary, $page, $theme)
    {
		$locale = $request->getLocale();

		$themes = $em->getRepository(Theme::class)->getTheme($locale);
		$query = $em->getRepository(News::class)->getEntitiesPagination($page, $theme, $locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		if((new MobileDetect())->isMobile() or $functionsLibrary->isApplication())
			$pagination->setPageRange(3);

		$pagination->setCustomParameters(['align' => 'center']);
		
		return $this->render('mobile/News/index.html.twig', [
			'themes' => $themes,
			'currentPage' => $page,
			'pagination' => $pagination
		]);
    }

    #[Route('/mobile/selectThemeForIndexNew', name: 'ap_newsmobile_selectthemeforindexnew')]
	public function selectThemeForIndexNew(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_news');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_newsmobile_index', ['page' => 1, 'theme' => $theme->getTitle()]));
	}

    #[Route('/mobile/read/{id}', name: 'ap_newsmobile_read', requirements: ['id' => '\d+'])]
	public function read(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(News::class)->find($id);

		if($entity->getArchive())
			throw new GoneHttpException("Archived");

		return $this->render('mobile/News/read.html.twig', [
			'entity' => $entity
		]);
	}

	#[Route('/mobile/search', name: 'ap_newsmobile_search')]
	public function search(Request $request, EntityManagerInterface $em, SearchEngine $searchEngine, ParameterBagInterface $parameterBag, PaginatorNativeSQL $paginator, FunctionsLibrary $functionsLibrary)
	{
        $form = $this->createForm(SearchEngineType::class);
		$form->handleRequest($request);

		$data = $form->getData();
		$type = $request->query->get("type", "text");
		$page = $request->query->get("page", 1);
		$keyword = $request->query->get("keyword", null);

		if($form->isSubmitted())
			$keyword = $form->get("query")->getData();

		$num_results_on_page = 10;
		$searchEngine->setParams($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."search".DIRECTORY_SEPARATOR.$_ENV["SEARCH_SQLITE_PATH"], $num_results_on_page);

		$path = "../../private/search/".$_ENV["SEARCH_SQLITE_PATH"];

		$connectionParams = [
			'url' => 'sqlite://'.$path
		];

		$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);

		// Web
		$startTimer = microtime(true);
		$pagination = $paginator->paginate(
			$searchEngine->getSQLQuery($keyword, $request->getLocale()),
			($request->query->has("page") and $type == "text") ? $page : 1,
			$num_results_on_page,
			$conn
		);

		if((new MobileDetect())->isMobile() or $functionsLibrary->isApplication())
			$pagination->setPageRange(3);

		$pagination->setCustomParameters(['align' => 'center']);
		$pagination->setParam('type', 'text');
		$pagination->setParam('keyword', $keyword);

		$total = $pagination->getTotalItemCount();
		$total_pages = ceil($total / $num_results_on_page);

		$datas = $this->getDataSearch($em, $pagination);

		$stopTimer = microtime(true);

		$execution_time = round($stopTimer - $startTimer, 7) * 1000;

		// Image
		$searchEngine->setType("image");

		$paginationImage = $paginator->paginate(
			$searchEngine->getSQLQuery($keyword, $request->getLocale()),
			($request->query->has("page") and $type == "image") ? $page : 1,
			$num_results_on_page,
			$conn,
			$searchEngine->countDatas($keyword, $request->getLocale())
		);

		if((new MobileDetect())->isMobile() or $functionsLibrary->isApplication())
			$paginationImage->setPageRange(3);

		$paginationImage->setCustomParameters(['align' => 'center']);
		$paginationImage->setParam('type', 'image');
		$paginationImage->setParam('keyword', $keyword);

		$totalImage = $paginationImage->getTotalItemCount();
		$total_pages_image = ceil($totalImage / $num_results_on_page);

		$dataImages = $this->getDataSearch($em, $paginationImage);

		$stopTimer = microtime(true);

		$execution_time_image = round($stopTimer - $startTimer, 7) * 1000;

		return $this->render("mobile/News/search.html.twig", [
			'searchRequest' => $keyword,
			'form' => $form->createView(),
			'results' => $datas,
			'pagination' => $pagination,
			'resultImages' => $dataImages,
			'paginationImage' => $paginationImage,
			'total' => $total,
			'totalImage' => $totalImage,
			'keyword' => $keyword,
			'num_results_on_page' => $num_results_on_page,
			'execution_time' => $execution_time,
			'execution_time_image' => $execution_time_image
		]);
	}

	private function getDataSearch($em, $pagination): Array
	{
		$datas = [];

		foreach($pagination->getItems() as $result) {
			$entity = $em->getRepository($this->getClassNameFromTableName($em, $result["classname"]))->find($result["id"]);
			$datas[$result["id"]."_".$result["classname"]]["entity"] = $entity;
			$route = $entity->getShowRoute();

			switch ($result["classname"]) {
				case "news":
					$route = "ap_newsmobile_read";
					break;
				case "video":
					$route = "ap_videomobile_read";
					break;
				case "photo":
					$route = "ap_photomobile_read";
					break;
				case "testimony":
					$route = "ap_testimonymobile_read";
					break;
				case "witchcraft":
					$route = "ap_witchcraftmobile_read";
					break;
			}

			$datas[$result["id"]."_".$result["classname"]]["showRoute"] = $route;
		}

		return $datas;
	}

	private function getClassNameFromTableName($em, $table)
	{
		$classNames = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		foreach ($classNames as $className) {
			$classMetaData = $em->getClassMetadata($className);
			if ($table == $classMetaData->getTableName()) {
				return $classMetaData->getName();
			}
		}
		return null;
	}

    #[Route('/mobile/selectlanguage/{language}', name: 'ap_newsmobile_selectlanguage')]
	public function selectLanguage(Request $request, $language)
    {
		$session = $request->getSession();
		$request->setLocale($language);
		$session->set('_locale', $language);
		return $this->redirect($this->generateUrl('ap_newsmobile_index', ["page" => 1]));
    }

	#[Route('/mobile/page/{page}', name: 'ap_pagemobile_page')]
	public function page(Request $request, EntityManagerInterface $em, String $page)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), $page);

        return $this->render('mobile/Page/page.html.twig', ['entity' => $entity]);
    }

	#[Route('/mobile/news/new', name: 'ap_newsmobile_new')]
	public function newAction(Request $request, EntityManagerInterface $em)
	{
        $entity = new News();

		$entity->setLicence($em->getRepository(Licence::class)->getOneLicenceByLanguageAndInternationalName($request->getLocale(), "CC-BY-NC-ND 3.0"));

        $form = $this->createForm(NewsUserParticipationType::class, $entity, ["language" => $request->getLocale()]);

        return $this->render('mobile/News/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
	}

	#[Route('/mobile/news/create', name: 'ap_newsmobile_create')]
	public function create(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$entity = new News();

		$user = $this->getUser();
        $form = $this->createForm(NewsUserParticipationType::class, $entity, ['language' => $request->getLocale()]);
        $form->handleRequest($request);
		
		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);
		
		$entity->setState($state);
		$entity->setLanguage($language);

        if ($form->isValid())
		{
			if(is_object($user) and !$entity->getIsAnonymous())
				$entity->setAuthor($user);
			else
			{
				$anonymousUser = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);
				$entity->setAuthor($anonymousUser);
				$entity->setIsAnonymous(1);
			}
			
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

			$this->addFlash('success', $translator->trans('news.validate.ThankForYourParticipationText', [], 'validators'));
			
			return $this->redirect($this->generateUrl('ap_newsmobile_index'));
        }

        return $this->render('mobile/News/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
	}
}