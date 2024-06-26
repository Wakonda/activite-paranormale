<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Theme;
use App\Entity\News;
use App\Entity\Book;
use App\Entity\Cartography;
use App\Entity\EventMessage;
use App\Entity\Photo;
use App\Entity\Testimony;
use App\Entity\Video;
use App\Entity\Document;
use App\Entity\CreepyStory;
use App\Entity\Movies\Movie;
use App\Entity\Movies\TelevisionSerie;
use Symfony\Component\HttpFoundation\Request;
use Ausi\SlugGenerator\SlugGenerator;

/**
 * Theme controller.
 *
 */
class ThemeController extends AbstractController
{
	public function indexAction(Request $request, EntityManagerInterface $em)
	{
		$locale = $request->getLocale();

		$parentTheme = $em->getRepository(Theme::class)->getThemeParent($locale);
		$theme = $em->getRepository(Theme::class)->getTheme($locale);
		$nbrTheme = $em->getRepository(Theme::class)->nbrTheme($locale);

		return $this->render('index/Theme/index.html.twig', [
			'nbrTheme' => $nbrTheme,
			'parentTheme' => $parentTheme,
			'theme' => $theme
		]);
	}	
	
	public function showAction(TranslatorInterface $translator, EntityManagerInterface $em, Request $request, $theme, $id)
	{
		$theme = str_replace('-', '/', $theme);
		$entity = $em->getRepository(Theme::class)->find($id);
		$childEntities = $em->getRepository(Theme::class)->findBy(["parentTheme" => $entity]);

		$childEntities = !empty($childEntities) ? $childEntities : [$entity];

		$abbreviationLanguage = $entity->getLanguage()->getAbbreviation();

		foreach($childEntities as $childEntity) {
			$id = $childEntity->getId();
			$theme = str_replace('-', '/', $childEntity->getTitle());

			$childArray = [
				$translator->trans('news.index.Actualite', [], 'validators') => [
					"number" => $em->getRepository(News::class)->countAllEntitiesPublicationByTheme($id),
					"path" => (!in_array($abbreviationLanguage, explode(",", $_ENV["LANGUAGES"]))) ? $this->generateUrl("News_World", ["language" => $abbreviationLanguage, "themeId" => $id, "theme" => $theme]) : $this->generateUrl("News_Index", ["page" => 1, "theme" => $theme])
				],
				$translator->trans('book.index.Book', [], 'validators') => [
					"number" => $em->getRepository(Book::class)->countAllEntitiesPublicationByTheme($id),
					"path" => $this->generateUrl("Book_Index", ["idTheme" => $id, "theme" => $theme])
				],
				$translator->trans('cartography.index.Cartography', [], 'validators') => [
					"number" => $em->getRepository(Cartography::class)->countAllEntitiesPublicationByTheme($id),
					"path" => $this->generateUrl("Cartography_Index", ["idTheme" => $id, "theme" => $theme])
				],
				$translator->trans('eventMessage.index.Event', [], 'validators') => [
					"number" => $em->getRepository(EventMessage::class)->countAllEntitiesPublicationByTheme($id),
					"path" => $this->generateUrl("EventMessage_Tab", ["id" => $id, "theme" => $theme])
				],
				$translator->trans('photo.index.Photo', [], 'validators') => [
					"number" => $em->getRepository(Photo::class)->countAllEntitiesPublicationByTheme($id),
					"path" => (!in_array($abbreviationLanguage, explode(",", $_ENV["LANGUAGES"]))) ? $this->generateUrl("Photo_World", ["language" => $abbreviationLanguage, "themeId" => $id, "theme" => $theme]) : $this->generateUrl("Photo_TabPicture", ["id" => $id, "theme" => $theme])
				],
				$translator->trans('testimony.index.Testimony', [], 'validators') => [
					"number" => $em->getRepository(Testimony::class)->countAllEntitiesPublicationByTheme($id),
					"path" => $this->generateUrl("Testimony_Tab", ["id" => $id, "theme" => $theme])
				],
				$translator->trans('video.index.Video', [], 'validators') => [
					"number" => $em->getRepository(Video::class)->countAllEntitiesPublicationByTheme($id),
					"path" => (!in_array($abbreviationLanguage, explode(",", $_ENV["LANGUAGES"]))) ? $this->generateUrl("Video_World", ["language" => $abbreviationLanguage, "themeId" => $id, "theme" => $theme]) : $this->generateUrl("Video_Tab", ["id" => $id, "theme" => $theme])
				],
				$translator->trans('document.index.Document', [], 'validators') => [
					"number" => $em->getRepository(Document::class)->countAllEntitiesPublicationByTheme($id),
					"path" => $this->generateUrl("Document_Index", ["themeId" => $id, "theme" => $theme])
				],
				$translator->trans('movie.index.Movie', [], 'validators') => [
					"number" => $em->getRepository(Movie::class)->countAllEntitiesPublicationByTheme($id),
					"path" => $this->generateUrl("Movie_Index", ["idTheme" => $id, "theme" => $theme])
				],
				$translator->trans('televisionSerie.index.TelevisionSerie', [], 'validators') => [
					"number" => $em->getRepository(TelevisionSerie::class)->countAllEntitiesPublicationByTheme($id),
					"path" => $this->generateUrl("TelevisionSerie_Index", ["idTheme" => $id, "theme" => $theme])
				],
				$translator->trans('creepyStory.index.CreepyStory', [], 'validators') => [
					"number" => $em->getRepository(CreepyStory::class)->countAllEntitiesPublicationByTheme($id),
					"path" => $this->generateUrl("CreepyStory_Tab", ["id" => $id, "theme" => $theme])
				]
			];
			
			uksort($childArray, function($a, $b) { $generator = new SlugGenerator; return $generator->generate($a) <=> $generator->generate($b); });
			$publications[$childEntity->getTitle()] = $childArray;
		}

		uksort($publications, function($a, $b) { $generator = new SlugGenerator; return $generator->generate($a) <=> $generator->generate($b); });

		return $this->render('index/Theme/show.html.twig', [
			"entity" => $entity,
			"publications" => $publications
		]);
	}	

	public function saveAction(EntityManagerInterface $em, $theme, $id)
	{
		$theme = str_replace('-', '/', $theme);
		
		$entity = $em->getRepository(Theme::class)->find($id);
		
		return $this->render('index/Theme/save.html.twig', [
			'entity' => $entity
		]);
	}

	public function downloadAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Theme::class)->find($id);

		$response = new Response();
		$response->setContent(file_get_contents($entity->getAssetImagePath().$entity->getPdfTheme()));

		$response->headers->set('Content-type', mime_content_type($entity->getAssetImagePath().$entity->getPdfTheme()));
		$response->headers->set('Content-Disposition', 'attachment; filename="'.$entity->getPdfTheme().'"');
		$response->headers->set("Content-Transfer-Encoding", "Binary");
		
		return $response;
	}

	/* FONCTION DE COMPTAGE */
	public function countByLanguage(EntityManagerInterface $em, Request $request)
	{
		return new Response($em->getRepository(Theme::class)->nbrTheme($request->getLocale()));
	}
}