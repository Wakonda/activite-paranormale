<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\Video;
use App\Entity\Theme;

require_once realpath(__DIR__."/../../vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php");

class VideoMobileController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page, $theme)
    {
		$em = $this->getDoctrine()->getManager();
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
		
		return $this->render('mobile/Video/index.html.twig', array(
			'themes' => $themes,
			'currentPage' => $page,
			'pagination' => $pagination
		));
    }
	
	public function selectThemeForIndexNewAction(Request $request)
	{
		$themeId = $request->request->get('theme_news');
		
		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_videomobile_index', array('page' => 1, 'theme' => $theme->getTitle())));
	}

	public function readAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Video::class)->find($id);
		
		if($entity->getArchive())
			throw new GoneHttpException();
		
		return $this->render('mobile/Video/read.html.twig', array(
			'entity' => $entity
		));
	}
}