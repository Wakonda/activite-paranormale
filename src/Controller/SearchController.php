<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Form\Type\SearchEngineType;
use App\Service\SearchEngine;
use App\Service\PaginatorNativeSQL;

class SearchController extends AbstractController
{
	public function searchAction(Request $request, EntityManagerInterface $em, SearchEngine $searchEngine, ParameterBagInterface $parameterBag, PaginatorNativeSQL $paginator)
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

		$pagination->setCustomParameters(['align' => 'center']);
		$pagination->setParam('type', 'text');
		$pagination->setParam('keyword', $keyword);
		
		$total = $pagination->getTotalItemCount();
		$total_pages = ceil($total / $num_results_on_page);
		
		$datas = [];

		foreach($pagination->getItems() as $result) {
			$datas[$result["id"]."_".$result["classname"]] = $em->getRepository($this->getClassNameFromTableName($em, $result["classname"]))->find($result["id"]);
		}
		
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
		$paginationImage->setParam('type', 'image');
		$paginationImage->setParam('keyword', $keyword);
		
		$totalImage = $paginationImage->getTotalItemCount();
		$total_pages_image = ceil($totalImage / $num_results_on_page);
		
		$dataImages = [];

		foreach($paginationImage->getItems() as $result) {
			$dataImages[$result["id"]."_".$result["classname"]] = $em->getRepository($this->getClassNameFromTableName($em, $result["classname"]))->find($result["id"]);
		}

		$stopTimer = microtime(true);

		$execution_time_image = round($stopTimer - $startTimer, 7) * 1000;

		return $this->render("search/Search/index.html.twig", [
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
}