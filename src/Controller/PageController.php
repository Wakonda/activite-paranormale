<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Page;
use App\Entity\President;
use App\Service\RSSFeed;

class PageController extends AbstractController
{
    public function aboutAction(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "about");

        return $this->render('page/Page/about.html.twig', ['entity' => $entity]);
    }

	public function copyrightAction(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "copyright");
		
		$images = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . '/public/extended/photo/pictures.json'), true);

        return $this->render('page/Page/copyright.html.twig', ['entity' => $entity, "images" => $images]);
    }

	public function cookieAction(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "cookie");

        return $this->render('page/Page/cookie.html.twig', ['entity' => $entity]);
    }

	public function faqAction(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "faq");

        return $this->render('page/Page/faq.html.twig', ['entity' => $entity]);
    }

	public function privacyPolicyAction(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "privacyPolicy");

        return $this->render('page/Page/privacyPolicy.html.twig', ['entity' => $entity]);
    }

	public function descriptionMetaTagAction(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "descriptionMetaTag");

		$textPage = ($entity == null) ? "" : trim(strip_tags($entity->getText()));
        return new Response($textPage);
    }

	public function keywordsMetaTagAction(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "keywordsMetaTag");

		$textPage = ($entity == null) ? "" : trim(strip_tags($entity->getText()));
        return new Response($textPage);
    }
	
	public function wordPresidentAction(Request $request, EntityManagerInterface $em)
    {
		$entities = $em->getRepository(President::class)->getPresidentsIndex($request->getLocale());

        return $this->render('page/Page/wordPresident.html.twig', ['entities' => $entities]);
    }

	public function displayLogoAction(Request $request, EntityManagerInterface $em)
	{
		$entity = $em->getRepository(President::class)->getPresidentIndex($request->getLocale());

		return $this->render("page/Page/displayLogo.html.twig", [
			"entity" => $entity
		]);
	}

	public function wordPresidentArchiveAction(Request $request, EntityManagerInterface $em)
	{
		$entities = $em->getRepository(President::class)->getPresidentArchive($request->getLocale());

		return $this->render('page/Page/wordPresidentArchive.html.twig', ['entities' => $entities]);
	}
	
	public function wordPresidentReadArchiveAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(President::class)->find($id);

		return $this->render('page/Page/wordPresidentReadArchive.html.twig', ['entity' => $entity]);
	}

	public function cryptocurrency(Request $request, EntityManagerInterface $em, $title) {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), $title);

        return $this->render('page/Page/cryptocurrency.html.twig', ['entity' => $entity]);
	}
	
	// GENERIC
	public function getPageByInternationalNameAction(Request $request, EntityManagerInterface $em, $internationalName, $isTitle = false)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), $internationalName);

        return $this->render('page/Page/generic.html.twig', ['entity' => $entity, "isTitle" => $isTitle]);
    }

	// RSS Feed
	public function indexRSSFeedAction()
	{
		return $this->render('page/RSSFeed/index.html.twig');
	}

	public function generateRSSFeedAction(Request $request, RSSFeed $rss)
	{
		return new Response($rss->generateFeed($request->query));
	}
}