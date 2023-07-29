<?php

namespace App\Controller\Mobile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Photo;
use App\Entity\Theme;

require_once realpath(__DIR__."/../../../vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php");

class PhotoMobileController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page, $theme)
    {
		$locale = $request->getLocale();

		$query = $em->getRepository(Photo::class)->getEntitiesPagination($page, $theme, $locale);
		$themes = $em->getRepository(Theme::class)->getTheme($locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		$mobileDetector = new \Mobile_Detect;
		
		if($mobileDetector->isMobile())
			$pagination->setPageRange(1);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('mobile/Photo/index.html.twig', [
			'pagination' => $pagination,
			'currentPage' => $page,
			'themes' => $themes
		]);
    }
	
	public function selectThemeForIndexPhotoAction(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_news');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_photomobile_index', ['page' => 1, 'theme' => $theme->getTitle()]));
	}

	public function readAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Photo::class)->find($id);
		
		return $this->render('mobile/Photo/read.html.twig', [
			'entity' => $entity
		]);
	}
}