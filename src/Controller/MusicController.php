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

class MusicController extends AbstractController
{
    public function indexAction(Request $request)
    {
		$form = $this->createForm(ArtistSearchType::class, null, ["locale" => $request->getLocale()]);
		
		return $this->render('music/Music/index.html.twig', [
			"form" => $form->createView()
		]);
    }

	public function listDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
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
		
		$form = $this->createForm(ArtistSearchType::class, null, ["locale" => $request->getLocale()]);
		
		parse_str($request->query->get($form->getName()), $datas);

		$form->submit($datas[$form->getName()]);
		
        $entities = $em->getRepository(Artist::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $form->getData());
		$iTotal = $em->getRepository(Artist::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $form->getData(), true);

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
			$row[] = !empty($genre = $entity->getGenre()) ? $genre->getTitle() : null;
			$row[] = '<a href="'.$this->generateUrl("Music_Album", ['id' => $entity->getId(), 'title_slug' => $entity->getTitle()]).'" >'.$translator->trans('music.index.Listen', [], 'validators').'</a>';

			$output['aaData'][] = $row;
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
	
	public function musicAction(EntityManagerInterface $em, $id, $music)
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

	public function countMusiqueAction(Request $request, EntityManagerInterface $em)
	{
		$countMusic = $em->getRepository(Music::class)->countMusic($request->getLocale());
		return new Response($countMusic);
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