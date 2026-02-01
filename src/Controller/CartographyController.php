<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
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
    #[Route('/cartography/{idTheme}/{theme}', name: 'Cartography_Index', defaults: ['theme' => null, 'idTheme' => null], requirements: ['theme' => '.+', 'idTheme' => '\d+'])]
    public function index(Request $request, EntityManagerInterface $em, $idTheme, $theme)
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

    #[Route('/cartography/show/{id}/{title_slug}', name: 'Cartography_Show', defaults: ['title_slug' => null], requirements: ['title_slug' => '.+'])]
	public function show(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Cartography::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

        return $this->render('cartography/Cartography/show.html.twig', [
			'entity' => $entity,
		]);
    }

	// Cartography of the world
    #[Route('/cartography/world/{language}/{themeId}/{theme}', name: 'Cartography_World', defaults: ['language' => 'all', 'themeId' => 0, 'theme' => null], requirements: ['theme' => '.+'])]
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

		return $this->render('cartography/Cartography/world.html.twig', [
			'flags' => $flags,
			'themes' => $themes,
			'title' => implode(" - ", $title),
			'theme' => empty($theme) ? null : $theme
		]);
	}

    #[Route('/cartography/selectThemeForIndexWorldAction/{language}', name: 'Cartography_SelectThemeForIndexWorld', defaults: ['language' => 'all'])]
	public function selectThemeForIndexWorldAction(Request $request, EntityManagerInterface $em, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');

		$theme = $em->getRepository(Theme::class)->find($themeId);
		return new Response($this->generateUrl('Cartography_World', ['language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
	}

    #[Route('/cartography/worlddatatables/{language}/{themeId}', name: 'Cartography_WorldDatatables', defaults: ['language' => 'all', 'themeId' => 0])]
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
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="'.addslashes($entity->getLanguage()->getTitle()).'" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="'.addslashes($entity->getTitle()).'" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

    #[Route('/cartography/datatables', name: 'Cartography_ListDatatables')]
	public function listDatatables(Request $request, EntityManagerInterface $em, APImgSize $imgSize)
	{
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++) {
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

		$form = $this->createForm(CartographySearchType::class, null, ["locale" => $request->getLocale()]);
		
		parse_str($request->query->get($form->getName()), $datas);

		$form->submit($datas[$form->getName()]);
		
		$formData = $form->getData();
	
		if($request->query->has("action") and $request->query->get("action") == "reset")
			$formData = [];

        $entities = $em->getRepository(Cartography::class)->getAllCartographyPlacesByLanguage($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $formData);
		$iTotal = $em->getRepository(Cartography::class)->getAllCartographyPlacesByLanguage($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $formData, true);

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
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="'.addslashes($entity->getTitle()).'" style="width: '.$photo[0].'">';
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