<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Movies\TelevisionSerie;
use App\Entity\Movies\EpisodeTelevisionSerie;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Theme;
use App\Form\Type\TelevisionSerieSearchType;
use Knp\Component\Pager\PaginatorInterface;

class TelevisionSerieController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page, $idTheme)
    {
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
	
	public function showAction(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(TelevisionSerie::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('movie/TelevisionSerie/show.html.twig', ['entity' => $entity]);
	}
	
	public function episodeAction(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(EpisodeTelevisionSerie::class)->find($id);
		
		if($entity->getTelevisionSerie()->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getTelevisionSerie()->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('movie/TelevisionSerie/showEpisode.html.twig', ['entity' => $entity]);
	}

	public function byGenreAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $idGenre, $titleGenre, $page)
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
	public function countAction(EntityManagerInterface $em, $language)
	{
		return new Response($em->getRepository(TelevisionSerie::class)->countByLanguage($language));
	}
	
	public function seasonAction(EntityManagerInterface $em, Int $id, String $title_slug, Int $season)
	{
		$televisionSerie = $em->getRepository(TelevisionSerie::class)->find($id);
		$entities = $em->getRepository(EpisodeTelevisionSerie::class)->findBy(["televisionSerie" => $televisionSerie, "season" => $season], ["episodeNumber" => "ASC"]);
		
		return $this->render("movie/TelevisionSerie/season.html.twig", ["entities" => $entities, "televisionSerie" => $televisionSerie, "season" => $season]);
	}
}