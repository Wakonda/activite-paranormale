<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

use App\Entity\Page;
use App\Entity\UsefulLLink;
use App\Entity\Language;

class UsefulLinkController extends AbstractController
{
    public function indexAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $request->getLocale()]);
		$page = $em->getRepository(Page::class)->findOneBy(["language" => $language, "internationalName" => "development"]);

		return $this->render('usefullink/UsefulLink/index.html.twig', ["page" => $page]);
    }
}