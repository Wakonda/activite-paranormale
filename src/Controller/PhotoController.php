<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Theme;
use App\Entity\Photo;
use App\Entity\Language;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;

class PhotoController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em)
    {
		$locale = $request->getLocale();

		$entities = $em->getRepository(Photo::class)->getAllPhotoByThemeAndLanguage($locale);
		$nbrPicture = $em->getRepository(Photo::class)->nbrPicture($locale);

		$datas = [];

		foreach($entities as $entity)
			$datas[$entity["parentTheme"]][] = $entity;

		return $this->render('photo/Photo/index.html.twig', [
			'datas' => $datas,
			'nbrPicture' => $nbrPicture
		]);
    }
	
	public function tabPictureAction(Request $request, $id, $theme)
	{
		return $this->render('photo/Photo/tabPicture.html.twig', [
			'themeDisplay' => $theme,
			'themeId' => $id
		]);
	}

	public function tabPictureDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $themeId)
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

        $entities = $em->getRepository(Photo::class)->getTabPicture($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Photo::class)->getTabPicture($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];

			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].';">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function readAction(Request $request, EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Photo::class)->find($id);

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(Photo::class)->getPreviousAndNextEntities($entity, $request->getLocale());

		return $this->render('photo/Photo/readPicture.html.twig', [
			'previousAndNextEntities' => $previousAndNextEntities,
			'entity' => $entity
		]);
	}

	public function countByLanguage(EntityManagerInterface $em, Request $request)
	{
		return new Response($em->getRepository(Photo::class)->nbrPicture($request->getLocale()));
	}

	// INDEX
	public function sliderAction(Request $request, EntityManagerInterface $em)
	{
		$entities = $em->getRepository(Photo::class)->getSliderNew($request->getLocale());
		return $this->render("photo/Photo/slider.html.twig", [
			"entities" => $entities
		]);
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(EntityManagerInterface $em, APHtml2Pdf $html2pdf, $id)
	{
		$entity = $em->getRepository(Photo::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The photo does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("photo/Photo/pdfVersion.html.twig", ["entity" => $entity]);
		
		return $html2pdf->generatePdf($content->getContent());
	}

	// Photo of the world
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

		return $this->render('photo/Photo/world.html.twig', [
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

		return new Response($this->generateUrl('Photo_World', array('language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle())));
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

        $entities = $em->getRepository(Photo::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Photo::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getSameTopicsAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Photo::class)->find($id);
		$sameTopics = $em->getRepository(Photo::class)->getSameTopics($entity);
		
		return $this->render("photo/Photo/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}
}