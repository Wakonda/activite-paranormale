<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\History;

class HistoryController extends AbstractController
{
    public function showAction(Request $request, EntityManagerInterface $em, $id, $titleEntity, $path)
    {
		$entity = $em->getRepository(History::class)->find($id);

        return $this->render('index/History/show.html.twig', ['entities' => $entity->getHistoryDetails(), 'titleEntity' => base64_decode($titleEntity), 'path' => base64_decode($path)]);
    }
}