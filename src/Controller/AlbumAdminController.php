<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
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
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		if($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			
			if(!empty($form->get("tracklist")->getData())) {
				foreach(json_decode($form->get("tracklist")->getData(), true) as $code => $data) {
					$music = new Music();
					$music->setWikidata($code);
					$music->setMusicPiece($data["title"]);
					
					if(isset($data["identifiers"])) {
						$identifiers = $data["identifiers"];
						$music->setIdentifiers(json_encode($identifiers));
					
						$found_key = array_search('YouTube video ID', array_column($identifiers, 'identifier'));

						if(isset($identifiers[$found_key]) and $identifiers[$found_key]["identifier"] == "YouTube video ID" and !empty($d = $identifiers[$found_key]["value"])) {
							$music->setEmbeddedCode('<iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/'.$d.'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>');
						}
					}

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

				$em->flush();
			}
		}
	}

    public function indexAction()
    {
		$twig = 'music/AlbumAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }

    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'music/AlbumAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
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
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $locale]);
    }

    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = AlbumAdminType::class;
		$entity = new Album();

		$twig = 'music/AlbumAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = AlbumAdminType::class;

		$twig = 'music/AlbumAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $request->getLocale()]);
    }

	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = AlbumAdminType::class;
		$twig = 'music/AlbumAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $request->getLocale()]);
    }

    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGenericAction($em, $id);
    }

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $entity->getArtist()->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			<a href='".$this->generateUrl('Album_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			<a href='".$this->generateUrl('Album_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
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
		
		$results = [];
		
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
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
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
			$row = [];
			$row[] = $entity->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('Album_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Album_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
}