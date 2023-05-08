<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Logo;

/**
 * Logo controller.
 *
 */
class LogoController extends AbstractController
{
	public function displayLogoAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(Logo::class)->getOneLogoByLanguageAndIsActive($request->getLocale());
	
		return $this->render("page/Logo/displayLogo.html.twig", [
			"entity" => $entity
		]);
	}
	
	public function readLogoAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(Logo::class)->find($id);
	
		return $this->render("page/Logo/readLogo.html.twig", [
			"entity" => $entity
		]);
	}
}