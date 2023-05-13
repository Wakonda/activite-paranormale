<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Page;
use App\Entity\President;
use App\Service\RSSFeed;

class PageController extends AbstractController
{
    public function aboutAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "about");

        return $this->render('page/Page/about.html.twig', ['entity' => $entity]);
    }

	public function copyrightAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "copyright");
		
		$images = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . '/public/extended/photo/pictures.json'), true);

        return $this->render('page/Page/copyright.html.twig', ['entity' => $entity, "images" => $images]);
    }

	public function cookieAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "cookie");

        return $this->render('page/Page/cookie.html.twig', ['entity' => $entity]);
    }

	public function faqAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "faq");

        return $this->render('page/Page/faq.html.twig', ['entity' => $entity]);
    }

	public function privacyPolicyAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "privacyPolicy");

        return $this->render('page/Page/privacyPolicy.html.twig', ['entity' => $entity]);
    }

	public function descriptionMetaTagAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "descriptionMetaTag");

		$textPage = ($entity == null) ? "" : trim(strip_tags($entity->getText()));
        return new Response($textPage);
    }

	public function keywordsMetaTagAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "keywordsMetaTag");

		$textPage = ($entity == null) ? "" : trim(strip_tags($entity->getText()));
        return new Response($textPage);
    }
	
	public function wordPresidentAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(President::class)->getPresidentIndex($request->getLocale());

        return $this->render('page/Page/wordPresident.html.twig', ['entity' => $entity]);
    }

	public function wordPresidentArchiveAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$entities = $em->getRepository(President::class)->getPresidentArchive($request->getLocale());

		return $this->render('page/Page/wordPresidentArchive.html.twig', ['entities' => $entities]);
	}
	
	public function wordPresidentReadArchiveAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(President::class)->find($id);

		return $this->render('page/Page/wordPresidentReadArchive.html.twig', ['entity' => $entity]);
	}
	
	// GENERIC
	public function getPageByInternationalNameAction(Request $request, $internationalName, $isTitle = false)
    {
		$em = $this->getDoctrine()->getManager();
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