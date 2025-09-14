<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\WebDirectorySEO;

class WebDirectorySEOController extends AbstractController
{
	#[Route('/directory/seo', name: 'WebDirectorySEO_Index')]
    public function index(EntityManagerInterface $em)
    {
		$entities = $em->getRepository(WebDirectorySEO::class)->findAll();

        return $this->render('webdirectoryseo/WebDirectorySEO/index.html.twig', [
			'entities' => $entities
		]);
    }

	#[Route('/directory/seo/{id}', name: 'WebDirectorySEO_Show')]
    public function show($id, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(WebDirectorySEO::class)->find($id);

        return $this->render('webdirectoryseo/WebDirectorySEO/show.html.twig', [
			'entity' => $entity
		]);
    }
}