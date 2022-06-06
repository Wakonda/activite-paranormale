<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

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

	public function listDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$em = $this->getDoctrine()->getManager();

		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->get('search')["value"];

		$sortByColumn = array();
		$sortDirColumn = array();
			
		for($i=0 ; $i<intval($order = $request->query->get('order')); $i++)
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
			"aaData" => array()
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

	public function albumAction($id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entities = $em->getRepository(Album::class)->getMusicsByAlbum($id);
		$artist = $em->getRepository(Artist::class)->find($id);
		
		return $this->render('music/Music/album.html.twig', array(
			"artist" => $artist,
			'entities' => $entities
		));	
	}

	public function listenAction($id, $artist, $artistId, $album)
	{
		$em = $this->getDoctrine()->getManager();

		$entities = $em->getRepository(Music::class)->findBy(array("album" => $id));
		$artist = $em->getRepository(Artist::class)->find($artistId);
		$album = $em->getRepository(Album::class)->find($id);
		
		return $this->render('music/Music/listen.html.twig', array(
			"artist" => $artist,
			"album" => $album,
			'entities' => $entities
		));	
	}
	
	public function musicAction($id, $music, $album, $albumId)
	{
		$em = $this->getDoctrine()->getManager();

		$entity = $em->getRepository(Music::class)->find($id);
		$album = $em->getRepository(Album::class)->find($albumId);
		
		return $this->render('music/Music/music.html.twig', array(
			"album" => $album,
			'entity' => $entity
		));
	}

	public function downloadAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Music::class)->find($id);

		$response = new Response();
		$response->headers->set("Content-Type", "application/force-download");
		$response->headers->set('Content-Disposition', 'attachment; filename="'.$entity->getMusicPieceFile().'"');
		
		return $response;
	}

	public function countMusiqueAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$countMusic = $em->getRepository(Music::class)->countMusic($request->getLocale());
		return new Response($countMusic);
	}
	
	public function musicGenreAction(Request $request, Int $genreId, String $genreTitle)
	{
		$em = $this->getDoctrine()->getManager();
		$musicGenre = $em->getRepository(MusicGenre::class)->find($genreId);
		$entities = $em->getRepository(Artist::class)->findBy(["genre" => $musicGenre]);
		
		return $this->render('music/Music/genre.html.twig', array(
			"musicGenre" => $musicGenre,
			'entities' => $entities
		));
	}
}