<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

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

	public function selectLanguageAction(Request $request, $lang)
    {
		$session = $request->getSession();
		$request->setLocale($lang);
		$session->set('_locale', $lang);

		return $this->redirect($this->generateUrl('Index_Index'));
    }
}