<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\Page;
use App\Entity\UsefulLink;
use App\Entity\Language;

class UsefulLinkController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page)
    {
		$language = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $request->getLocale()]);
		$entity = $em->getRepository(Page::class)->findOneBy(["language" => $language, "internationalName" => UsefulLink::DEVELOPMENT_FAMILY]);

		$query = $em->getRepository(UsefulLink::class)->getUsefulLLinks(UsefulLink::DEVELOPMENT_FAMILY, $page, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('usefullink/UsefulLink/index.html.twig', ["entity" => $entity, 'pagination' => $pagination]);
    }
}