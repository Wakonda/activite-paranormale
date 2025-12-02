<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Theme;
use App\Entity\CreepyStory;
use App\Entity\Language;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;

class CreepyStoryController extends AbstractController
{
	#[Route('/creepy_story', name: 'CreepyStory_Index')]
    public function index(Request $request, EntityManagerInterface $em)
    {
		$locale = $request->getLocale();

		$entities = $em->getRepository(CreepyStory::class)->getAllCreepyStoryByThemeAndLanguage($locale);
		$nbr = $em->getRepository(CreepyStory::class)->countCreepyStory($locale);

		$datas = [];

		foreach($entities as $entity)
			$datas[$entity["parentTheme"]][] = $entity;

		ksort($datas);

		return $this->render('creepyStory/CreepyStory/index.html.twig', [
			'datas' => $datas,
			'nbr' => $nbr
		]);
    }

	#[Route('/creepy_story/tab/{id}/{theme}', name: 'CreepyStory_Tab', requirements: ['theme' => ".+"])]
	public function tab(Request $request, $id, $theme)
	{
		return $this->render('creepyStory/CreepyStory/tab.html.twig', [
			'themeDisplay' => $theme,
			'themeId' => $id
		]);
	}

	#[Route('/creepy_story/tabdatatables/{themeId}', name: 'CreepyStory_TabDatatables')]
	public function tabDatatables(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $themeId)
	{
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];;
		$language = $request->getLocale();

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(CreepyStory::class)->getTab($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(CreepyStory::class)->getTab($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];

			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="'.addslashes($entity->getTitle()).'" style="width: '.$photo[0].'">';
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	#[Route('/creepy_story/read/{id}/{title_slug}', name: 'CreepyStory_Read', defaults: ['title_slug' => null])]
	public function read(Request $request, EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(CreepyStory::class)->find($id);

		$previousAndNextEntities = $em->getRepository(CreepyStory::class)->getPreviousAndNextEntities($entity, $request->getLocale());

		return $this->render('creepyStory/CreepyStory/read.html.twig', [
			'previousAndNextEntities' => $previousAndNextEntities,
			'entity' => $entity
		]);
	}

	public function countAction(EntityManagerInterface $em, Request $request)
	{
		return new Response($em->getRepository(CreepyStory::class)->countCreepyStory($request->getLocale()));
	}

	// ENREGISTREMENT PDF
	#[Route('/creepy_story/pdfversion/{id}', name: 'CreepyStory_Pdfversion')]
	public function pdfVersion(EntityManagerInterface $em, APHtml2Pdf $html2pdf, $id)
	{
		$entity = $em->getRepository(CreepyStory::class)->find($id);

		if(empty($entity))
			throw $this->createNotFoundException("The Creepy Story does not exist");

		$content = $this->render("creepyStory/CreepyStory/pdfVersion.html.twig", ["entity" => $entity]);

		return $html2pdf->generatePdf($content->getContent());
	}

	public function selectThemeForIndexWorldAction(Request $request, EntityManagerInterface $em, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');

		$theme = $em->getRepository(Theme::class)->find($themeId);
		return new Response($this->generateUrl('Photo_World', ['language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
	}

	public function getSameTopicsAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(CreepyStory::class)->find($id);
		$sameTopics = $em->getRepository(CreepyStory::class)->getSameTopics($entity);

		return $this->render("creepyStory/CreepyStory/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}

	public function random() {
		return $this->render("creepyStory/CreepyStory/random.html.twig");
	}

	#[Route('/creepy_story/random', name: 'CreepyStory_LoadRandom')]
	public function loadRandom(Request $request, EntityManagerInterface $em, APDate $date, APImgSize $imgSize) {
		$locale = $request->getLocale();
		$entity = $em->getRepository(CreepyStory::class)->getRandom($locale);

		$output = [];

		if(!empty($entity)) {
			preg_match('/^.{0,750}(?:.*?)\b/iu', $entity->getText(), $matches);

			$photo = $imgSize->adaptImageSize(null, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());

			$output = [
				"title" => $entity->getTitle(),
				"text" => substr($entity->getText(), 0, 250)."...",
				"author" => $entity->authorToString(),
				"date" => $date->doDate($request->getLocale(), $entity->getPublicationDate()),
				"photo" => '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="'.addslashes($entity->getTitle()).'" class="card-img img-fluid">',
				"showRoute" => $this->generateUrl("CreepyStory_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug()])
			];
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
}