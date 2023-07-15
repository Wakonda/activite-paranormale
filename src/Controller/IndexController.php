<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class IndexController extends AbstractController
{
    public function indexAction(Request $request, SessionInterface $session)
    {
		if((new \Mobile_Detect)->isTablet() or (new \Mobile_Detect)->isMobile())
			$session->set('v', "v3");
		else {
			if($request->query->has("v") and !empty($v = $request->query->get("v")))
				$session->set('v', $v);
		}

        return $this->render('index/Index/index.html.twig');
    }
	
	public function selectLanguageAction(Request $request, SessionInterface $session, $lang)
    {
		$request->setLocale($lang);
		$session->set('_locale', $lang);

		return $this->redirect($this->generateUrl('Index_Index'));
    }
}