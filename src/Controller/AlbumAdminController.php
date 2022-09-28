<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Music;
use App\Entity\Language;
use App\Form\Type\AlbumAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Album controller.
 *
 */
class AlbumAdminController extends AdminGenericController
{
	protected $entityName = 'Album';
	protected $className = Album::class;

	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "Album_Admin_Index"; 
	protected $showRoute = "Album_Admin_Show";
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));

	}

	public function postValidationAction($form, $entityBindded)
	{
		if($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			
			if(!empty($form->get("tracklist")->getData())) {
				// dd(json_decode($form->get("tracklist")->getData(), true));
				foreach(json_decode($form->get("tracklist")->getData(), true) as $code => $data) {
					// dd($code, $value);
					$music = new Music();
					$music->setWikidata($code);
					$music->setMusicPiece($data["title"]);
					$music->setIdentifiers(json_encode($data["identifiers"]));
					
					if(isset($data["duration"])) {
						if($data["duration"]["unit"] == "second") {
							$time = $data["duration"]["amount"];
							$music->setLength(sprintf('%02d:%02d:%02d', ($time/3600),($time/60%60), $time%60));
						}
					}

					$music->setAlbum($entityBindded);
					
					$searchForDoublons = $em->getRepository(Music::class)->countForDoublons($music);
					
					if($searchForDoublons == 0)
						$em->persist($music);
				}
				// die("ok)");
				$em->flush();
			}
		}
	}

    public function indexAction()
    {
		$twig = 'music/AlbumAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }

    public function showAction($id)
    {
		$twig = 'music/AlbumAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = AlbumAdminType::class;
		$entity = new Album();
		
		$locale = $request->getLocale();

		if ($request->query->has("artistId")) {
			$artist = $this->getDoctrine()->getManager()->getRepository(Artist::class)->find($request->query->get("artistId"));
			$entity->setArtist($artist);
			$locale = $artist->getLanguage()->getAbbreviation();
			$entity->setLanguage($artist->getLanguage());
		}

		$twig = 'music/AlbumAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $locale]);
    }

    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = AlbumAdminType::class;
		$entity = new Album();

		$twig = 'music/AlbumAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

    public function editAction(Request $request, $id)
    {
		$formType = AlbumAdminType::class;

		$twig = 'music/AlbumAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $request->getLocale()]);
    }

	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = AlbumAdminType::class;
		$twig = 'music/AlbumAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $request->getLocale()]);
    }

    public function deleteAction($id)
    {
		return $this->deleteGenericAction($id);
    }

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = array();
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $entity->getArtist()->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			<a href='".$this->generateUrl('Album_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			<a href='".$this->generateUrl('Album_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function autocompleteAction(Request $request)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		
		if(is_numeric($locale)) {
			$language = $this->getDoctrine()->getManager()->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}
		
		$datas =  $this->getDoctrine()->getManager()->getRepository(Album::class)->getAutocomplete($locale, $query);
		
		$results = array();
		
		foreach($datas as $data)
		{
			$obj = new \stdClass();
			$obj->id = $data->getId();
			$obj->text = $data->getTitle();
			
			$results[] = $obj;
		}

        return new JsonResponse(["results" => $results]);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Album_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}
	
	public function wikidataAction(Request $request, \App\Service\Wikidata $wikidata)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getAlbumDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}

    public function indexByArtistAction(Int $artistId)
    {
		$twig = 'music/AlbumAdmin/indexByArtist.html.twig';
		return $this->render($twig, ["artistId" => $artistId]);
    }

	public function indexByArtistDatatablesAction(Request $request, TranslatorInterface $translator, Int $artistId)
	{
		$em = $this->getDoctrine()->getManager();
		
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexByArtistAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $artistId);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexByArtistAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $artistId, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

		foreach($entities as $entity)
		{
			$row = array();
			$row[] = $entity->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('Album_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Album_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
}