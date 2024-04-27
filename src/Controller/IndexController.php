<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Doctrine\ORM\EntityManagerInterface;

class IndexController extends AbstractController
{
    public function indexAction(Request $request)
    {

		
		$session = $request->getSession();
		if((new \Mobile_Detect)->isTablet() or (new \Mobile_Detect)->isMobile())
			$session->set('v', "v3");
		else {
			if($request->query->has("v") and !empty($v = $request->query->get("v")))
				$session->set('v', $v);
		}

        return $this->render('index/Index/index.html.twig');
    }

	public function application() {
		return $this->render("index/Index/application.html.twig");
	}

	public function downloadApplication() {
		$file = $this->getParameter('kernel.project_dir') . '/public/extended/photo/application/activite-paranormale-1.0.0.apk';
		return $this->file($file, 'activite-paranormale-1.0.0.apk');
	}

	public function selectLanguageAction(Request $request, $lang)
    {
		$session = $request->getSession();
		$request->setLocale($lang);
		$session->set('_locale', $lang);

		return $this->redirect($this->generateUrl('Index_Index'));
    }
	
	public function world(Request $request, EntityManagerInterface $em, $language, $themeId, $currentRoute) {
		return $this->render("index/Generic/_world.html.twig", [
			"counter" => [
				"news" => $em->getRepository(\App\Entity\News::class)->getDatatablesForWorldIndex($language, $themeId, 0, 0, null, null, null, true),
				"biography" => $em->getRepository(\App\Entity\Biography::class)->getDatatablesForWorldIndex($language, 0, 0, null, null, null, true),
				"book" => $em->getRepository(\App\Entity\Book::class)->getDatatablesForWorldIndex($language, $themeId, 0, 0, null, null, null, true),
				"cartography" => $em->getRepository(\App\Entity\Cartography::class)->getDatatablesForWorldIndex($language, $themeId, 0, 0, null, null, null, true),
				"eventMessage" => $em->getRepository(\App\Entity\EventMessage::class)->getDatatablesForWorldIndex($language, $themeId, 0, 0, null, null, null, true),
				"photo" => $em->getRepository(\App\Entity\Photo::class)->getDatatablesForWorldIndex($language, $themeId, 0, 0, null, null, null, true),
				"video" => $em->getRepository(\App\Entity\Video::class)->getDatatablesForWorldIndex($language, $themeId, 0, 0, null, null, null, true),
				"witchcraft" => $em->getRepository(\App\Entity\Grimoire::class)->getDatatablesForWorldIndex($language, $themeId, 0, 0, null, null, null, true)
			],
			"route" => $currentRoute
		]);
	}

	public function magic() {
		return $this->render("index/Index/magic.html.twig");
	}
}