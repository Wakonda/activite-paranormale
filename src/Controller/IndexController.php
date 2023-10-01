<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
}