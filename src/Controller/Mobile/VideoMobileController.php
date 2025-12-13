<?php

namespace App\Controller\Mobile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Video;
use App\Entity\Theme;
use App\Service\FunctionsLibrary;
use Detection\MobileDetect;

class VideoMobileController extends AbstractController
{
	#[Route('/mobile/video/{page}/{theme}', name: 'ap_videomobile_index', defaults: ['page' => 1, 'theme' => null], requirements: ['page' => '\d+', 'theme' => '.+'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, FunctionsLibrary $functionsLibrary, $page, $theme)
    {
		$locale = $request->getLocale();

		$themes = $em->getRepository(Theme::class)->getTheme($locale);
		$query = $em->getRepository(Video::class)->getEntitiesPagination($page, $theme, $locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		if((new MobileDetect())->isMobile() or $functionsLibrary->isApplication())
			$pagination->setPageRange(3);

		$pagination->setCustomParameters(['align' => 'center']);
		
		return $this->render('mobile/Video/index.html.twig', [
			'themes' => $themes,
			'currentPage' => $page,
			'pagination' => $pagination
		]);
    }

	#[Route('/mobile/video/selectThemeForIndex', name: 'ap_videomobile_selectthemeforindex')]
	public function selectThemeForIndexNew(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_news');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_videomobile_index', ['page' => 1, 'theme' => $theme->getTitle()]));
	}

    #[Route('/mobile/video/read/{id}', name: 'ap_videomobile_read', requirements: ['id' => '\d+'])]
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