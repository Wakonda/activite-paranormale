<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Music;
use App\Entity\Album;
use App\Entity\Language;
use App\Entity\Licence;
use App\Entity\MusicBiography;
use App\Form\Type\MusicAdminType;
use App\Service\Spotify;
use App\Service\Identifier;
use App\Service\ConstraintControllerValidator;

/**
 * musicGestion controller.
 *
 */
class MusicAdminController extends AdminGenericController
{
	protected $entityName = 'Music';
	protected $className = Music::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Music_Admin_Index"; 
	protected $showRoute = "Music_Admin_Show";
	protected $illustrations = [["field" => "musicPieceFile"]];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$data = $form->all();

		if(isset($data['music_selector']) and $entityBindded->getMusicPieceFile() == "")
			$entityBindded->setMusicPieceFile($data['music_selector']->getNormData());
	
		// Check for Doublons
		if(!empty($entityBindded->getAlbum())) {
			$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

			if($searchForDoublons > 0)
				$form->get('musicPiece')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
		}
		if(empty($entityBindded->getMusicPieceFile()) and empty($entityBindded->getEmbeddedCode())) {
			$form->get('musicPieceFile')->addError(new FormError($translator->trans('admin.error.NotBlankVideoOrAudio', [], 'validators')));
			$form->get('embeddedCode')->addError(new FormError($translator->trans('admin.error.NotBlankVideoOrAudio', [], 'validators')));
		}
		foreach ($form->get('musicBiographies') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($em, $entityBindded, $form, "musicBiographies");
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		$originalMusics = new ArrayCollection($em->getRepository(MusicBiography::class)->findBy(["music" => $entityBindded->getId()]));
		
		foreach($originalMusics as $originalMusic)
		{
			if(false === $entityBindded->getMusicBiographies()->contains($originalMusic))
			{
				$em->remove($originalMusic);
			}
		}

		foreach($entityBindded->getMusicBiographies() as $mb)
		{
			if(!empty($mb->getBiography())) {
				$mb->setMusic($entityBindded);
				$em->persist($mb);
			}
		}

		$em->flush();
	}

    public function indexAction()
    {
		$twig = 'music/MusicAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'music/MusicAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = MusicAdminType::class;
		$entity = new Music();
		
		if ($request->query->has("albumId")) {
			$entity->setAlbum($em->getRepository(Album::class)->find($request->query->get("albumId")));
		}
		
		$language = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $request->getLocale()));

		$twig = 'music/MusicAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ["language" => $language]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = MusicAdminType::class;
		$entity = new Music();
		
		$twig = 'music/MusicAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(EntityManagerInterface $em, $id)
    {
		$formType = MusicAdminType::class;

		$twig = 'music/MusicAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = MusicAdminType::class;
		$twig = 'music/MusicAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType);
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
			$row[] = !empty($album = $entity->getAlbum()) ? $album->getArtist()->getTitle() : $entity->getArtist()->getTitle();
			$row[] = $entity->getMusicPiece();
			$row[] = "
			 <a href='".$this->generateUrl('Music_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Music_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function chooseExistingFileAction()
    {
		$webPath = $this->getParameter('kernel.project_dir').'/public/extended/flash/Music/MP3/';
	
		$finder = new Finder();
		$finder->files()->in($webPath);
		$filesArray = [];
		
		foreach ($finder as $file)
		{
			$filesArray[] = $file->getRelativePathname();
		}
	
		return $this->render('music/MusicAdmin/chooseExistingFile.html.twig', array(
			"filesArray" => $filesArray
		));	
    }

    public function indexByAlbumAction(EntityManagerInterface $em, Int $albumId)
    {
		$album = $em->getRepository(Album::class)->find($albumId);
		$spotifyId = null;

		if(!empty($album->getIdentifiers())) {
			$identifiers = json_decode($album->getIdentifiers(), true);
			$key = array_search(Identifier::SPOTIFY_ALBUM_ID , array_column($identifiers, "identifier"));

			if($key !== false)
				$spotifyId = $identifiers[$key]["value"];
		}

		$twig = 'music/MusicAdmin/indexByAlbum.html.twig';
		return $this->render($twig, ["albumId" => $albumId, "spotifyId" => $spotifyId]);
    }

	public function indexByAlbumDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, Int $albumId)
	{
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexByAlbumAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $albumId);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexByAlbumAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $albumId, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getMusicPiece();
			$row[] = "
			 <a href='".$this->generateUrl('Music_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Music_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function wikidataAction(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getMusicDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}

	public function spotifyMusic(EntityManagerInterface $em, Spotify $spotify, $albumId, $spotifyId) {
		$album = $em->getRepository(Album::class)->find($albumId);
		$licence = $em->getRepository(Licence::class)->findOneBy(["title" => "CC-BY-NC-ND 3.0", "language" => $album->getLanguage()]);
		$language = $album->getLanguage();

		$datas = $spotify->getAlbumTracks($spotifyId);

		foreach($datas as $data) {
			$music = $em->getRepository(Music::class)->findOneBy(["album" => $album, "musicPiece" => $data["name"]]);

			if(!empty($music))
				continue;

			$music = new Music();
			$music->setAlbum($album);
			$music->setMusicPiece($data["name"]);

			$seconds = floor($data["duration_ms"] / 1000);
			$hours = floor($seconds / 3600);
			$minutes = floor(($seconds % 3600) / 60);
			$remainingSeconds = $seconds % 60;

			$music->setLength(sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds));
			$music->setIdentifiers(json_encode([["identifier" => Identifier::SPOTIFY_TRACK_ID, "value" => $data["id"]]]));

			$em->persist($music);
		}

		$em->flush();

		return $this->redirect($this->generateUrl("Album_Admin_Show", ["id" => $album->getId()]));
	}
	
	public function autocompleteFestival(Request $request, EntityManagerInterface $em)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		
		if(is_numeric($locale)) {
			$language = $em->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}
		
		$datas =  $em->getRepository(\App\Entity\EventMessage::class)->getAutocompleteFestival($locale, $query);
		
		$results = [];
		
		foreach($datas as $data)
		{
			$obj = new \stdClass();
			$obj->id = $data->getId();
			$obj->text = $data->getTitle(). " (".$data->getYearFrom().")";
			
			$results[] = $obj;
		}

        return new JsonResponse(["results" => $results]);
	}
}