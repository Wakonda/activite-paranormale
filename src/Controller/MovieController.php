<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Movies\Movie;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Theme;
use App\Form\Type\MovieSearchType;
use Knp\Component\Pager\PaginatorInterface;

class MovieController extends AbstractController
{
	#[Route('/movie/{page}/{idTheme}/{theme}', name: 'Movie_Index', defaults: ['page' => 1, 'theme' => null, 'idTheme' => null], requirements: ['page' => '\d+', 'theme' => '.+', 'idTheme' => '\d+'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page, $idTheme)
    {
		$datas = [];

		if(!empty($idTheme))
			$datas["theme"] = $em->getRepository(Theme::class)->find($idTheme);

		$form = $this->createForm(MovieSearchType::class, $datas, ["locale" => $request->getLocale()]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
			$datas = $form->getData();

		if($request->query->has("reset"))
			$datas = [];

		$query = $em->getRepository(Movie::class)->getMovies($datas, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('movie/Movie/index.html.twig', ['pagination' => $pagination, 'form' => $form->createView()]);
    }

	#[Route('/movie/read/{id}/{title_slug}', name: 'Movie_Show', defaults: ['title_slug' => null], requirements: ['id' => '\d+'])]
	public function show(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Movie::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('movie/Movie/show.html.twig', ['entity' => $entity]);
	}

	#[Route('/movie/genre/{idGenre}/{title_slug}/{page}', name: 'ByGenreMovie_Index', defaults: ['page' => 1], requirements: ['idGenre' => '\d+'])]
	public function byGenre(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $idGenre, $title_slug, $page)
	{
		$nbMessageByPage = 12;

		$genre = $em->getRepository(GenreAudiovisual::class)->find($idGenre);
		$query = $em->getRepository(Movie::class)->getMoviesByGenre($idGenre, $nbMessageByPage, $page);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			$nbMessageByPage /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);
	
		return $this->render("movie/Movie/byGenre.html.twig", ['pagination' => $pagination, "genre" => $genre ]);
	}

	/* FONCTION DE COMPTAGE */
	public function countByLanguage(EntityManagerInterface $em, Request $request)
	{
		return new Response($em->getRepository(Movie::class)->countMovieByLanguage($request->getLocale()));
	}
}