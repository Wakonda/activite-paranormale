<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Movies\Movie;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Language;
use App\Entity\Theme;
use App\Form\Type\MovieSearchType;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\APImgSize;
use App\Service\APDate;

class MovieController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page)
    {
		$em = $this->getDoctrine()->getManager();

		$nbMessageByPage = 12;

		$form = $this->createForm(MovieSearchType::class, null, ["locale" => $request->getLocale()]);
		$form->handleRequest($request);
		$datas = null;

		if ($form->isSubmitted() && $form->isValid())
			$datas = $form->getData();

		$query = $em->getRepository(Movie::class)->getMovies($datas, $nbMessageByPage, $page, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('movie/Movie/index.html.twig', ['pagination' => $pagination, 'form' => $form->createView()]);
    }
	
	public function showAction($id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Movie::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('movie/Movie/show.html.twig', ['entity' => $entity]);
	}

	public function byGenreAction(Request $request, PaginatorInterface $paginator, $idGenre, $titleGenre, $page)
	{
		$em = $this->getDoctrine()->getManager();
		$nbMessageByPage = 12;

		$genre = $em->getRepository(GenreAudiovisual::class)->find($idGenre);
		$query = $em->getRepository(Movie::class)->getMoviesByGenre($idGenre, $nbMessageByPage, $page);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);
	
		return $this->render("movie/Movie/byGenre.html.twig", ['pagination' => $pagination, "genre" => $genre ]);
	}

	/* FONCTION DE COMPTAGE */
	public function countAction($language)
	{
		$em = $this->getDoctrine()->getManager();

		return new Response($em->getRepository(Movie::class)->countMovieByLanguage($language));
	}
}