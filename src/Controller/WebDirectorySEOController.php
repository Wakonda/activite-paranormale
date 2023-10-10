<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\WebDirectorySEO;

class WebDirectorySEOController extends AbstractController
{
    public function index(EntityManagerInterface $em)
    {
		$entities = $em->getRepository(WebDirectorySEO::class)->findAll();

        return $this->render('webdirectoryseo/WebDirectorySEO/index.html.twig', [
			'entities' => $entities
		]);
    }

    public function show($id, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(WebDirectorySEO::class)->find($id);

        return $this->render('webdirectoryseo/WebDirectorySEO/show.html.twig', [
			'entity' => $entity
		]);
    }
}