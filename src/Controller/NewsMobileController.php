<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Knp\Component\Pager\PaginatorInterface;

use App\Form\Type\SearchEngineType;
use App\Service\SearchEngine;
use App\Service\PaginatorNativeSQL;

use App\Entity\News;
use App\Entity\Theme;

require_once realpath(__DIR__."/../../vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php");

class NewsMobileController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page, $theme)
    {
		$em = $this->getDoctrine()->getManager();
		$locale = $request->getLocale();

		$themes = $em->getRepository(Theme::class)->getTheme($locale);
		$query = $em->getRepository(News::class)->getEntitiesPagination($page, $theme, $locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		$mobileDetector = new \Mobile_Detect;
		
		if($mobileDetector->isMobile())
			$pagination->setPageRange(1);

		$pagination->setCustomParameters(['align' => 'center']);
		
		return $this->render('mobile/News/index.html.twig', array(
			'themes' => $themes,
			'currentPage' => $page,
			'pagination' => $pagination
		));
    }
	
	public function selectThemeForIndexNewAction(Request $request)
	{
		$themeId = $request->request->get('theme_news');
		
		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_newsmobile_index', array('page' => 1, 'theme' => $theme->getTitle())));
	}

	public function readAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(News::class)->find($id);
		
		if($entity->getArchive())
			throw new GoneHttpException("Archived");
		
		return $this->render('mobile/News/read.html.twig', array(
			'entity' => $entity
		));
	}
	
	public function searchAction(Request $request, SearchEngine $searchEngine, ParameterBagInterface $parameterBag, PaginatorNativeSQL $paginator)
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
		
		$em = $this->getDoctrine()->getManager();
		$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);

		// Web
		$startTimer = microtime(true);
		$pagination = $paginator->paginate(
			$searchEngine->getSQLQuery($keyword, $request->getLocale()),
			($request->query->has("page") and $type == "text") ? $page : 1,
			$num_results_on_page,
			$conn
		);
		
		$pagination->setCustomParameters(['align' => 'center']);
		$pagination->setParam('type', 'text');
		$pagination->setParam('keyword', $keyword);
		
		$total = $pagination->getTotalItemCount();
		$total_pages = ceil($total / $num_results_on_page);
		
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
		// dd($datas);
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
		
		$paginationImage->setCustomParameters(['align' => 'center']);
		$pagination->setParam('type', 'image');
		$pagination->setParam('keyword', $keyword);
		
		$totalImage = $paginationImage->getTotalItemCount();
		$total_pages_image = ceil($totalImage / $num_results_on_page);
		
		$dataImages = [];

		foreach($paginationImage->getItems() as $result) {
			$dataImages[$result["id"]."_".$result["classname"]] = $em->getRepository($this->getClassNameFromTableName($em, $result["classname"]))->find($result["id"]);
		}
		
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

	public function selectLanguageAction(Request $request, SessionInterface $session, $language)
    {
		$request->setLocale($language);
		$session->set('_locale', $language);
		return $this->redirect($this->generateUrl('ap_newsmobile_index', array("page" => 1)));
    }
}
