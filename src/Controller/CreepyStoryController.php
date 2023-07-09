<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

use App\Entity\Theme;
use App\Entity\CreepyStory;
use App\Entity\Language;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;

class CreepyStoryController extends AbstractController
{
    public function indexAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();

		$locale = $request->getLocale();
		
		$parentTheme = $em->getRepository(Theme::class)->getThemeParent($locale);
		$theme = $em->getRepository(Theme::class)->getTheme($locale);

		$nbrTheme = $em->getRepository(Theme::class)->nbrTheme($locale);
		$nbr = $em->getRepository(CreepyStory::class)->countCreepyStory($locale);

		for($i = 0; $i < $nbrTheme; $i++)
		{
			$nbrPictureByTheme[$i] = $em->getRepository(CreepyStory::class)->nbrByTheme($locale, $theme[$i]->getTitle());
			$tabThemeNbr[$i][0] = $theme[$i]->getTitle();
			$tabThemeNbr[$i][1] = $nbrPictureByTheme[$i];
		}

		return $this->render('creepyStory/CreepyStory/index.html.twig', [
			'parentTheme' => $parentTheme,
			'nbr' => $nbr,
			'tabThemeNbr' => $tabThemeNbr,
			'nbrTheme' => $nbrTheme,
			'theme' => $theme
		]);
    }
	
	public function tabAction(Request $request, $id, $theme)
	{
		return $this->render('creepyStory/CreepyStory/tab.html.twig', [
			'themeDisplay' => $theme,
			'themeId' => $id
		]);
	}

	public function tabDatatablesAction(Request $request, APImgSize $imgSize, APDate $date, $themeId)
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
		
        $entities = $em->getRepository(CreepyStory::class)->getTab($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(CreepyStory::class)->getTab($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];

			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'" >'.$entity->getTitle().'</a>';
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

		$entity = $em->getRepository(CreepyStory::class)->find($id);

		$previousAndNextEntities = $em->getRepository(CreepyStory::class)->getPreviousAndNextEntities($entity, $request->getLocale());

		return $this->render('creepyStory/CreepyStory/read.html.twig', [
			'previousAndNextEntities' => $previousAndNextEntities,
			'entity' => $entity
		]);
	}

	public function countAction($language)
	{
		$em = $this->getDoctrine()->getManager();
		$nbr = $em->getRepository(CreepyStory::class)->countCreepyStory($language);
		return new Response($nbr);
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(APHtml2Pdf $html2pdf, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(CreepyStory::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The Creepy Story does not exist");

		$content = $this->render("creepyStory/CreepyStory/pdfVersion.html.twig", ["entity" => $entity]);
		
		return $html2pdf->generatePdf($content->getContent());
	}

	public function selectThemeForIndexWorldAction(Request $request, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');

		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(Theme::class)->find($themeId);
		return new Response($this->generateUrl('Photo_World', ['language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
	}

	public function getSameTopicsAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(CreepyStory::class)->find($id);
		$sameTopics = $em->getRepository(CreepyStory::class)->getSameTopics($entity);
		
		return $this->render("creepyStory/CreepyStory/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}
}