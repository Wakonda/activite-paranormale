<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Partner;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;

class PartnerController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page)
    {
		$language = $request->getLocale();
		$em = $this->getDoctrine()->getManager();

		$query = $em->getRepository(Partner::class)->getPartners($language);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			8 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

        return $this->render('partner/Partner/index.html.twig', array(
				'pagination' => $pagination
		));
    }
}