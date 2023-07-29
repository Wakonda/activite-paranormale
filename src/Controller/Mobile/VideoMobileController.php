<?php

namespace App\Controller\Mobile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Video;
use App\Entity\Theme;

require_once realpath(__DIR__."/../../../vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php");

class VideoMobileController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page, $theme)
    {
		$locale = $request->getLocale();

		$themes = $em->getRepository(Theme::class)->getTheme($locale);
		$query = $em->getRepository(Video::class)->getEntitiesPagination($page, $theme, $locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		$mobileDetector = new \Mobile_Detect;
		
		if($mobileDetector->isMobile())
			$pagination->setPageRange(1);

		$pagination->setCustomParameters(['align' => 'center']);
		
		return $this->render('mobile/Video/index.html.twig', [
			'themes' => $themes,
			'currentPage' => $page,
			'pagination' => $pagination
		]);
    }
	
	public function selectThemeForIndexNewAction(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_news');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_videomobile_index', ['page' => 1, 'theme' => $theme->getTitle()]));
	}

	public function readAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Video::class)->find($id);
		
		if($entity->getArchive())
			throw new GoneHttpException();
		
		return $this->render('mobile/Video/read.html.twig', [
			'entity' => $entity
		]);
	}
}