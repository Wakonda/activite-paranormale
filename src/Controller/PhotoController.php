<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

use App\Entity\SurTheme;
use App\Entity\Theme;
use App\Entity\Photo;
use App\Entity\Language;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;

class PhotoController extends AbstractController
{
    public function indexAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();

		$lang = $request->getLocale();
		
		$surTheme = $em->getRepository(SurTheme::class)->getSurTheme($lang);

		$theme2 = $em->getRepository(Theme::class)->getTheme($lang);

		$nbrTheme = $em->getRepository(Theme::class)->nbrTheme($lang);
		$nbrPicture = $em->getRepository(Photo::class)->nbrPicture($lang);

		for($i = 0; $i < $nbrTheme; $i++)
		{
			$nbrPictureByTheme[$i] = $em->getRepository(Photo::class)->nbrPictureByTheme($lang, $theme2[$i]->gettitle());
			$tabThemeNbr[$i][0] = $theme2[$i]->gettitle();
			$tabThemeNbr[$i][1] = $nbrPictureByTheme[$i];
		}

		return $this->render('photo/Photo/index.html.twig', array(
			'surTheme' => $surTheme,
			'nbrPicture' => $nbrPicture,
			'tabThemeNbr' => $tabThemeNbr,
			'nbrTheme' => $nbrTheme,
			'theme' => $theme2
		));	
    }
	
	public function tabPictureAction(Request $request, $id, $theme)
	{
		return $this->render('photo/Photo/tabPicture.html.twig', array(
			'themeDisplay' => $theme,
			'themeId' => $id
		));	
	}

	public function tabPictureDatatablesAction(Request $request, APImgSize $imgSize, APDate $date, $themeId)
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
		
        $entities = $em->getRepository(Photo::class)->getTabPicture($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Photo::class)->getTabPicture($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

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

			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function readAction(Request $request, $id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();

		$picture = $em->getRepository(Photo::class)->find($id);

		if($picture->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $picture->getId(), "className" => base64_encode(get_class($picture))]));

		$previousAndNextEntities = $em->getRepository(Photo::class)->getPreviousAndNextEntities($picture, $request->getLocale());

		return $this->render('photo/Photo/readPicture.html.twig', array(
			'previousAndNextEntities' => $previousAndNextEntities,
			'picture' => $picture
		));
	}

	public function nbrPictureAction($lang)
	{
		$em = $this->getDoctrine()->getManager();
		$nbrPicture = $em->getRepository(Photo::class)->nbrPicture($lang);
		return new Response($nbrPicture);
	}

	// INDEX
	public function sliderAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$entities = $em->getRepository(Photo::class)->getSliderNew($request->getLocale());
		return $this->render("photo/Photo/slider.html.twig", array(
			"entities" => $entities
		));
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(APHtml2Pdf $html2pdf, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Photo::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The photo does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("photo/Photo/pdfVersion.html.twig", array("entity" => $entity));
		
		return $html2pdf->generatePdf($content->getContent());
	}

	// Photo of the world
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

		return $this->render('photo/Photo/world.html.twig', array(
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
		return new Response($this->generateUrl('Photo_World', array('language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle())));
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
		
        $entities = $em->getRepository(Photo::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Photo::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

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
	
	public function getSameTopicsAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Photo::class)->find($id);
		$sameTopics = $em->getRepository(Photo::class)->getSameTopics($entity);
		
		return $this->render("photo/Photo/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}
}