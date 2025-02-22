<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Artist;
use App\Entity\Album;
use App\Entity\Music;
use App\Entity\MusicGenre;
use App\Form\Type\ArtistSearchType;
use App\Service\APImgSize;

class MusicController extends AbstractController
{
    public function indexAction(Request $request)
    {
		$form = $this->createForm(ArtistSearchType::class, null, ["locale" => $request->getLocale()]);
		
		return $this->render('music/Music/index.html.twig', [
			"form" => $form->createView()
		]);
    }

	public function listDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APImgSize $imgSize)
	{
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}
		
		$form = $this->createForm(ArtistSearchType::class, null, ["locale" => $request->getLocale()]);
		
		parse_str($request->query->get($form->getName()), $datas);

		$form->submit($datas[$form->getName()]);

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];
		
        $entities = $em->getRepository(Artist::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $datas);
		$iTotal = $em->getRepository(Artist::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$logo = $imgSize->adaptImageSize(200, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];
			$row[] = $entity->getTitle().(!empty($entity->getPhotoIllustrationFilename()) ? '<div><img src="'.$request->getBasePath().'/'.$logo[2].'" class="bg-white" alt="'.addslashes($entity->getTitle()).'" style="width: '.$logo[0].'"></div>' : "");
			$row[] = !empty($genre = $entity->getGenre()) ? $genre->getTitle() : null;
			$row[] = '<a href="'.$this->generateUrl("Music_Album", ['id' => $entity->getId(), 'title_slug' => $entity->getTitle()]).'" >'.$translator->trans('music.index.Read', [], 'validators').'</a>';

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function albumAction(EntityManagerInterface $em, $id, $title_slug)
	{
		$entities = $em->getRepository(Album::class)->getMusicsByAlbum($id);
		$musicByArtists = $em->getRepository(Music::class)->getMusicsByArtist($id);

		$artist = $em->getRepository(Artist::class)->find($id);
		
		return $this->render('music/Music/album.html.twig', [
			"artist" => $artist,
			'entities' => $entities,
			'musicByArtists' => $musicByArtists
		]);
	}

	public function listenAction(EntityManagerInterface $em, $id, $artist, $artistId, $album)
	{
		$entities = $em->getRepository(Music::class)->findBy(["album" => $id]);
		$artist = $em->getRepository(Artist::class)->find($artistId);
		$album = $em->getRepository(Album::class)->find($id);
		
		return $this->render('music/Music/listen.html.twig', [
			"artist" => $artist,
			"album" => $album,
			'entities' => $entities
		]);
	}
	
	public function musicAction(EntityManagerInterface $em, $id, $music = null)
	{
		$entity = $em->getRepository(Music::class)->find($id);
		$album = $entity->getAlbum();
		$artist = !empty($album) ? $album->getArtist() : $entity->getArtist();

		return $this->render('music/Music/music.html.twig', [
			"album" => $album,
			'entity' => $entity,
			'artist' => $artist
		]);
	}

	public function downloadAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Music::class)->find($id);

		$response = new Response();
		$response->headers->set("Content-Type", "application/force-download");
		$response->headers->set('Content-Disposition', 'attachment; filename="'.$entity->getMusicPieceFile().'"');
		
		return $response;
	}

	public function countByLanguage(Request $request, EntityManagerInterface $em)
	{
		return new Response($em->getRepository(Artist::class)->countArtist($request->getLocale()));
	}
	
	public function musicGenreAction(Request $request, EntityManagerInterface $em, Int $genreId, String $genreTitle)
	{
		$musicGenre = $em->getRepository(MusicGenre::class)->find($genreId);
		$entities = $em->getRepository(Artist::class)->findBy(["genre" => $musicGenre]);
		
		return $this->render('music/Music/genre.html.twig', [
			"musicGenre" => $musicGenre,
			'entities' => $entities
		]);
	}
}