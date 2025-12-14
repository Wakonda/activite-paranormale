<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Music;
use App\Entity\Language;
use App\Entity\Licence;
use App\Form\Type\AlbumAdminType;
use App\Service\Spotify;
use App\Service\Identifier;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/album')]
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
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		if($form->isValid()) {
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
							$music->setEmbeddedCode('<iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/'.$d.'" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>');
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

	#[Route('/', name: 'Album_Admin_Index')]
    public function index()
    {
		$twig = 'music/AlbumAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Album_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'music/AlbumAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'Album_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = AlbumAdminType::class;
		$entity = new Album();
		
		$locale = $request->getLocale();

		if ($request->query->has("artistId")) {
			$artist = $em->getRepository(Artist::class)->find($request->query->get("artistId"));
			$entity->setArtist($artist);
			$locale = $artist->getLanguage()->getAbbreviation();
			$entity->setLanguage($artist->getLanguage());
		}

		$twig = 'music/AlbumAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $locale]);
    }

	#[Route('/create', name: 'Album_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = AlbumAdminType::class;
		$entity = new Album();

		$twig = 'music/AlbumAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/{id}/edit', name: 'Album_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = AlbumAdminType::class;

		$twig = 'music/AlbumAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/{id}/update', name: 'Album_Admin_Update', methods: ['POST'])]
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = AlbumAdminType::class;
		$twig = 'music/AlbumAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/{id}/delete', name: 'Album_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'Album_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
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

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/autocomplete', name: 'Album_Admin_Autocomplete')]
	public function autocompleteAction(Request $request, EntityManagerInterface $em)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		
		if(is_numeric($locale)) {
			$language = $em->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}
		
		$datas =  $em->getRepository(Album::class)->getAutocomplete($locale, $query);
		
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

	#[Route('/showImageSelectorColorbox', name: 'Album_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('Album_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'Album_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	#[Route('/wikidata', name: 'Album_Admin_Wikidata')]
	public function wikidataAction(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getAlbumDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}

    public function indexByArtist(EntityManagerInterface $em, Int $artistId)
    {
		$artist = $em->getRepository(Artist::class)->find($artistId);
		$spotifyId = null;

		if(!empty($artist->getIdentifiers())) {
			$identifiers = json_decode($artist->getIdentifiers(), true);
			
			if(!empty($identifiers)) {
				$key = array_search(Identifier::SPOTIFY_ARTIST_ID , array_column($identifiers, "identifier"));

				if($key !== false)
					$spotifyId = $identifiers[$key]["value"];
			}
		}

		$twig = 'music/AlbumAdmin/indexByArtist.html.twig';
		return $this->render($twig, ["artistId" => $artistId, "spotifyId" => $spotifyId]);
    }

	#[Route('/datatables/{artistId}', name: 'Album_Admin_IndexByArtistDatatables', methods: ['GET'])]
	public function indexByArtistDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, Int $artistId)
	{
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexByArtistAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $artistId);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexByArtistAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $artistId, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('Album_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Album_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/music/spotify/album/{artistId}/{spotifyId}', name: 'Spotify_Album')]
	public function spotifyAlbum(EntityManagerInterface $em, Spotify $spotify, $artistId, $spotifyId) {
		$artist = $em->getRepository(Artist::class)->find($artistId);
		$licence = $em->getRepository(Licence::class)->findOneBy(["title" => "CC-BY-NC-ND 3.0", "language" => $artist->getLanguage()]);
		$language = $artist->getLanguage();

		$datas = $spotify->getAlbumsByArtist($spotifyId);

		foreach($datas as $data) {
			$album = $em->getRepository(Album::class)->findOneBy(["artist" => $artist, "language" => $language, "title" => $data["name"]]);

			if(!empty($album))
				continue;

			$album = new Album();
			$album->setArtist($artist);
			$album->setLicence($licence);
			$album->setLanguage($language);
			$album->setTitle($data["name"]);
			$album->setReleaseYear($data["release_date"]);
			$album->setIdentifiers(json_encode([["identifier" => Identifier::SPOTIFY_ALBUM_ID, "value" => $data["id"]]]));
			
			$em->persist($album);
			
			foreach($data["tracks"] as $track) {
				$music = $em->getRepository(Music::class)->findOneBy(["album" => $album, "musicPiece" => $track["name"]]);

				if(!empty($music))
					continue;

				$music = new Music();
				$music->setAlbum($album);
				$music->setMusicPiece($track["name"]);

				$seconds = floor($track["duration_ms"] / 1000);
				$hours = floor($seconds / 3600);
				$minutes = floor(($seconds % 3600) / 60);
				$remainingSeconds = $seconds % 60;

				$music->setLength(sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds));
				$music->setIdentifiers(json_encode([["identifier" => Identifier::SPOTIFY_TRACK_ID, "value" => $track["id"]]]));

				$em->persist($music);
			}
		}

		$em->flush();

		return $this->redirect($this->generateUrl("Artist_Admin_Show", ["id" => $artist->getId()]));
	}
}