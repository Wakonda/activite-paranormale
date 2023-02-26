<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Movies\TelevisionSerie;
use App\Entity\Movies\EpisodeTelevisionSerie;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Language;
use App\Entity\Theme;
use App\Form\Type\TelevisionSerieSearchType;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\APImgSize;

class TelevisionSerieController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page, $idTheme)
    {
		$em = $this->getDoctrine()->getManager();

		$datas = [];

		if(!empty($idTheme))
			$datas["theme"] = $em->getRepository(Theme::class)->find($idTheme);

		$form = $this->createForm(TelevisionSerieSearchType::class, $datas, ["locale" => $request->getLocale()]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
			$datas = $form->getData();

		$query = $em->getRepository(TelevisionSerie::class)->getTelevisionSeries($datas, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('movie/TelevisionSerie/index.html.twig', ['pagination' => $pagination, 'form' => $form->createView()]);
    }
	
	public function showAction($id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(TelevisionSerie::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('movie/TelevisionSerie/show.html.twig', ['entity' => $entity]);
	}
	
	public function episodeAction($id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(EpisodeTelevisionSerie::class)->find($id);
		
		if($entity->getTelevisionSerie()->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getTelevisionSerie()->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('movie/TelevisionSerie/showEpisode.html.twig', ['entity' => $entity]);
	}

	public function byGenreAction(Request $request, PaginatorInterface $paginator, $idGenre, $titleGenre, $page)
	{
		$em = $this->getDoctrine()->getManager();

		$genre = $em->getRepository(GenreAudiovisual::class)->find($idGenre);
		$entities = $em->getRepository(TelevisionSerie::class)->getByGenre($idGenre);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);
	
		return $this->render("movie/TelevisionSerie/byGenre.html.twig", ['pagination' => $pagination, "genre" => $genre ]);
	}

	/* FONCTION DE COMPTAGE */
	public function countAction($language)
	{
		$em = $this->getDoctrine()->getManager();

		return new Response($em->getRepository(TelevisionSerie::class)->countByLanguage($language));
	}
	
	public function seasonAction(Int $id, String $title_slug, Int $season)
	{
		$em = $this->getDoctrine()->getManager();
		$televisionSerie = $em->getRepository(TelevisionSerie::class)->find($id);
		$entities = $em->getRepository(EpisodeTelevisionSerie::class)->findBy(["televisionSerie" => $televisionSerie, "season" => $season], ["episodeNumber" => "ASC"]);
		
		return $this->render("movie/TelevisionSerie/season.html.twig", ["entities" => $entities, "televisionSerie" => $televisionSerie, "season" => $season]);
	}
}