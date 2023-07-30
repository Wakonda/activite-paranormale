<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\WebDirectorySEO;

class WebDirectorySEOController extends AbstractController
{   
    public function indexAction(EntityManagerInterface $em, $page)
    {
		$entities = $em->getRepository(WebDirectorySEO::class)->findAll();
		
        return $this->render('webdirectoryseo/WebDirectorySEO/index.html.twig', [
			'entities' => $entities
		]);
    }
}