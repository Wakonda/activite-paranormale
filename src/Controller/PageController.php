<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Page;
use App\Entity\President;
use App\Service\RSSFeed;

class PageController extends AbstractController
{
	#[Route('/about', name: 'Page_About')]
    public function about(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "about");

        return $this->render('page/Page/about.html.twig', ['entity' => $entity]);
    }

	#[Route('/copyright', name: 'Page_Copyright')]
	public function copyright(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "copyright");
		
		$images = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . '/public/extended/photo/pictures.json'), true);

        return $this->render('page/Page/copyright.html.twig', ['entity' => $entity, "images" => $images]);
    }

	#[Route('/cookie', name: 'Page_Cookie')]
	public function cookie(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "cookie");

        return $this->render('page/Page/cookie.html.twig', ['entity' => $entity]);
    }

	#[Route('/faq', name: 'Page_Faq')]
	public function faq(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "faq");

        return $this->render('page/Page/faq.html.twig', ['entity' => $entity]);
    }

	#[Route('/privacy-policy', name: 'Page_PrivacyPolicy')]
	public function privacyPolicy(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "privacyPolicy");

        return $this->render('page/Page/privacyPolicy.html.twig', ['entity' => $entity]);
    }

	public function descriptionMetaTag(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "descriptionMetaTag");

		$textPage = ($entity == null) ? "" : trim(strip_tags($entity->getText()));
        return new Response($textPage);
    }

	public function keywordsMetaTag(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), "keywordsMetaTag");

		$textPage = ($entity == null) ? "" : trim(strip_tags($entity->getText()));
        return new Response($textPage);
    }

	public function wordPresident(Request $request, EntityManagerInterface $em)
    {
		$entities = $em->getRepository(President::class)->getPresidentsIndex($request->getLocale());

        return $this->render('page/Page/wordPresident.html.twig', ['entities' => $entities]);
    }

	public function displayLogo(Request $request, EntityManagerInterface $em)
	{
		$entity = $em->getRepository(President::class)->getPresidentIndex($request->getLocale());

		return $this->render("page/Page/displayLogo.html.twig", [
			"entity" => $entity
		]);
	}

	#[Route('/wordpresident/index', name: 'President_Archive_Index')]
	public function wordPresidentArchive(Request $request, EntityManagerInterface $em)
	{
		$entities = $em->getRepository(President::class)->getPresidentArchive($request->getLocale());

		return $this->render('page/Page/wordPresidentArchive.html.twig', ['entities' => $entities]);
	}

	#[Route('/wordpresident/read/{id}', name: 'President_Archive_Read')]
	public function wordPresidentReadArchive(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(President::class)->find($id);

		return $this->render('page/Page/wordPresidentReadArchive.html.twig', ['entity' => $entity]);
	}

	#[Route('/cryptocurrency/{title}', name: 'Page_Cryptocurrency')]
	public function cryptocurrency(Request $request, EntityManagerInterface $em, $title) {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), $title);

        return $this->render('page/Page/cryptocurrency.html.twig', ['entity' => $entity]);
	}
	
	// GENERIC
	public function getPagePartialByInternationalName(Request $request, EntityManagerInterface $em, $internationalName, $isTitle = false)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), $internationalName);

        return $this->render('page/Page/genericPartial.html.twig', ['entity' => $entity, "isTitle" => $isTitle]);
    }

	#[Route('/page/{internationalName}', name: 'Page_Generic')]
	public function getPageByInternationalName(Request $request, EntityManagerInterface $em, $internationalName, $isTitle = false)
    {
		$entity = $em->getRepository(Page::class)->getPageByLanguageAndType($request->getLocale(), $internationalName);

        return $this->render('page/Page/generic.html.twig', ['entity' => $entity, "isTitle" => $isTitle]);
    }

	// RSS Feed
	#[Route('/rss', name: 'Page_IndexRSSFeed')]
	public function indexRSSFeed(EntityManagerInterface $em)
	{
		$otherLanguages = $em->getRepository(\App\Entity\Language::class)->getAllLanguages();
		return $this->render('page/RSSFeed/index.html.twig', ["otherLanguages" => $otherLanguages]);
	}

	#[Route('/rssfeed', name: 'Page_GenerateRSSFeed')]
	public function generateRSSFeed(Request $request, RSSFeed $rss)
	{
		return new Response($rss->generateFeed($request->query));
	}
}