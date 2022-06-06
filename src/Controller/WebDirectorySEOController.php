<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\WebDirectorySEO;

class WebDirectorySEOController extends AbstractController
{   
    public function indexAction($page)
    {
		$em = $this->getDoctrine()->getManager();

		$entities = $em->getRepository(WebDirectorySEO::class)->findAll();
		
        return $this->render('webdirectoryseo/WebDirectorySEO/index.html.twig', array(
			'entities' => $entities
		));
    }
}