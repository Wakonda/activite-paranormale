<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use App\Form\Type\CartographyType;
use App\Entity\Cartography;
use App\Entity\Language;
use App\Entity\Theme;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Form\Type\CartographySearchType;

class CartographyController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, $idTheme)
    {
		$theme = null;
		
		if(!empty($idTheme))
			$theme = $em->getRepository(Theme::class)->find($idTheme);

		$obj = new \stdclass();
		$obj->theme = $theme;

		$form = $this->createForm(CartographySearchType::class, $obj, ["locale" => $request->getLocale()]);

        return $this->render('cartography/Cartography/index.html.twig', [
			"form" => $form->createView()
		]);
    }

	public function showAction(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Cartography::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

        return $this->render('cartography/Cartography/show.html.twig', [
			'entity' => $entity,
		]);
    }
	
	public function nbrGMapByLangAction(EntityManagerInterface $em, $lang)
	{
		$nbrGMapByLang = $em->getRepository(Cartography::class)->nbrGMapByLang($lang);
		return new Response($nbrGMapByLang);
	}

	// Cartography of the world
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

		return $this->render('cartography/Cartography/world.html.twig', [
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
		return new Response($this->generateUrl('Cartography_World', ['language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
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
		
        $entities = $em->getRepository(Cartography::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Cartography::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

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

	public function listDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize)
	{
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

		$form = $this->createForm(CartographySearchType::class, null, ["locale" => $request->getLocale()]);
		
		parse_str($request->query->get($form->getName()), $datas);

		$form->submit($datas[$form->getName()]);

        $entities = $em->getRepository(Cartography::class)->getAllCartographyPlacesByLanguage($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $form->getData());
		$iTotal = $em->getRepository(Cartography::class)->getAllCartographyPlacesByLanguage($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $form->getData(), true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];
			$row["latitud"] = $entity->getCoordXMap();
			$row["longitud"] = $entity->getCoordYMap();
			$row["id"] = $entity->getId();
			$row["infoWindow"] = $this->render("cartography/Cartography/_infowindow.html.twig", ["entity" => $entity])->getContent();
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'">';
			$row[] = "<a href='#title_cartography_maps' id='{$entity->getId()}' class='location_coordinates' data-latitud='{$entity->getCoordXMap()}' data-longitud='{$entity->getCoordYMap()}'>{$entity->getTitle()}</a>";
			$row[] = $entity->getTheme()->getTitle();
			$row[] = number_format($entity->getCoordXMap(), 2);
			$row[] = number_format($entity->getCoordYMap(), 2);

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
}