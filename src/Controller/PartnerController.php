<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Partner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class PartnerController extends AbstractController
{
	#[Route('/partner/{page}', name: 'Partner_Index', defaults: ['page' => 1])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page)
    {
		$language = $request->getLocale();

		$query = $em->getRepository(Partner::class)->getPartners($language);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			8 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

        return $this->render('partner/Partner/index.html.twig', [
				'pagination' => $pagination
		]);
    }
}