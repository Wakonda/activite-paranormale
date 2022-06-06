<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use App\Entity\Theme;
use App\Entity\SurTheme;
use App\Service\APImgSize;
use App\Service\APDate;

class IndexController extends AbstractController
{
    public function indexAction(Request $request, TranslatorInterface $translator, SessionInterface $session)
    {
		if($request->query->has("v") and !empty($v = $request->query->get("v")))
			$session->set('v', $v);

        return $this->render('index/Index/index.html.twig');
    }
	
	public function selectLanguageAction(Request $request, SessionInterface $session, $lang)
    {
		$request->setLocale($lang);
		$session->set('_locale', $lang);

		return $this->redirect($this->generateUrl('Index_Index'));
    }
}