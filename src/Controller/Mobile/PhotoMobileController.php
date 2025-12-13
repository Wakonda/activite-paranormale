<?php

namespace App\Controller\Mobile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Photo;
use App\Entity\Theme;
use App\Service\FunctionsLibrary;
use Detection\MobileDetect;

class PhotoMobileController extends AbstractController
{
    #[Route('/mobile/photo/{page}/{theme}', name: 'ap_photomobile_index', defaults: ['page' => 1, 'theme' => null], requirements: ['page' => '\d+', 'theme' => '.+'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, FunctionsLibrary $functionsLibrary, $page, $theme)
    {
		$locale = $request->getLocale();

		$query = $em->getRepository(Photo::class)->getEntitiesPagination($page, $theme, $locale);
		$themes = $em->getRepository(Theme::class)->getTheme($locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		if((new MobileDetect())->isMobile() or $functionsLibrary->isApplication())
			$pagination->setPageRange(3);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('mobile/Photo/index.html.twig', [
			'pagination' => $pagination,
			'currentPage' => $page,
			'themes' => $themes
		]);
    }

    #[Route('/mobile/photo/selectThemeForIndexPhoto', name: 'ap_photomobile_selectthemeforindexphoto')]
	public function selectThemeForIndexPhotoAction(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_news');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_photomobile_index', ['page' => 1, 'theme' => $theme->getTitle()]));
	}

    #[Route('/mobile/photo/read/{id}', name: 'ap_photomobile_read', requirements: ['id' => '\d+'])]
	public function readAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Photo::class)->find($id);
		
		return $this->render('mobile/Photo/read.html.twig', [
			'entity' => $entity
		]);
	}
}