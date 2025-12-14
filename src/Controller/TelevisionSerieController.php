<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Movies\TelevisionSerie;
use App\Entity\Movies\EpisodeTelevisionSerie;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Theme;
use App\Form\Type\TelevisionSerieSearchType;
use Knp\Component\Pager\PaginatorInterface;

class TelevisionSerieController extends AbstractController
{
	#[Route('/television_serie/{page}/{idTheme}/{theme}', name: 'TelevisionSerie_Index', defaults: ['page' => 1, 'theme' => null, 'idTheme' => null], requirements: ['page' => '\d+', 'theme' => '.+', 'idTheme' => '\d+'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page, $idTheme)
    {
		$datas = [];

		if(!empty($idTheme))
			$datas["theme"] = $em->getRepository(Theme::class)->find($idTheme);

		$form = $this->createForm(TelevisionSerieSearchType::class, $datas, ["locale" => $request->getLocale()]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
			$datas = $form->getData();

		if($request->query->has("reset"))
			$datas = [];

		$query = $em->getRepository(TelevisionSerie::class)->getTelevisionSeries($datas, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('movie/TelevisionSerie/index.html.twig', ['pagination' => $pagination, 'form' => $form->createView()]);
    }

	#[Route('/television_serie/{id}/{title_slug}', name: 'TelevisionSerie_Show', defaults: ['title_slug' => null], requirements: ['id' => '\d+'])]
	public function show(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(TelevisionSerie::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('movie/TelevisionSerie/show.html.twig', ['entity' => $entity]);
	}

	#[Route('/television_serie/episode/{id}/{title_slug}', name: 'TelevisionSerie_Episode', defaults: ['title_slug' => null], requirements: ['id' => '\d+'])]
	public function episode(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(EpisodeTelevisionSerie::class)->find($id);
		
		if($entity->getTelevisionSerie()->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getTelevisionSerie()->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('movie/TelevisionSerie/showEpisode.html.twig', ['entity' => $entity]);
	}

	#[Route('/television_serie/genre/{idGenre}/{titleGenre}/{page}', name: 'ByGenreTelevisionSerie_Index', defaults: ['page' => 1], requirements: ['idGenre' => '\d+'])]
	public function byGenre(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $idGenre, $titleGenre, $page)
	{
		$nbMessageByPage = 12;

		$genre = $em->getRepository(GenreAudiovisual::class)->find($idGenre);
		$entities = $em->getRepository(TelevisionSerie::class)->getByGenre($idGenre);
		$query = $em->getRepository(TelevisionSerie::class)->getTelevisionSeriesByGenre($idGenre, $nbMessageByPage, $page);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			$nbMessageByPage /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render("movie/TelevisionSerie/byGenre.html.twig", ['pagination' => $pagination, "genre" => $genre ]);
	}

	/* FONCTION DE COMPTAGE */
	public function countByLanguage(EntityManagerInterface $em, Request $request)
	{
		return new Response($em->getRepository(TelevisionSerie::class)->countByLanguage($request->getLocale()));
	}

	#[Route('/television_serie/{id}/{title_slug}/season/{season}', name: 'TelevisionSerie_Season', defaults: ['title_slug' => null], requirements: ['id' => '\d+'])]
	public function season(EntityManagerInterface $em, Int $id, String $title_slug, Int $season)
	{
		$televisionSerie = $em->getRepository(TelevisionSerie::class)->find($id);
		$entities = $em->getRepository(EpisodeTelevisionSerie::class)->findBy(["televisionSerie" => $televisionSerie, "season" => $season], ["episodeNumber" => "ASC"]);
		
		return $this->render("movie/TelevisionSerie/season.html.twig", ["entities" => $entities, "televisionSerie" => $televisionSerie, "season" => $season]);
	}
}