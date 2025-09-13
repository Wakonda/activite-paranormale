<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Deal;
use App\Entity\Language;

class DealController extends AbstractController
{
	#[Route('/deal/{page}', name: 'Deal_Index', defaults: ['page' => 1], requirements: ['page' => "\d+"])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page)
    {
		$query = $em->getRepository(Deal::class)->getDatas($request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('deal/Deal/index.html.twig', ['pagination' => $pagination]);
    }

	#[Route('/deal/read/{id}', name: 'Deal_Show', defaults: ['language' => null], requirements: ['id' => "\d+"])]
	public function show(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Deal::class)->find($id);

		return $this->render('deal/Deal/show.html.twig', ['entity' => $entity]);
	}
}