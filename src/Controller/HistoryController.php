<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\History;

class HistoryController extends AbstractController
{
    public function showAction(Request $request, $id, $titleEntity, $path)
    {
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(History::class)->find($id);

        return $this->render('index/History/show.html.twig', array('entities' => $entity->getHistoryDetails(), 'titleEntity' => $titleEntity, 'path' => base64_decode($path)));
    }
}