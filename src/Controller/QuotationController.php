<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Quotation;
use App\Entity\Biography;
use Knp\Component\Pager\PaginatorInterface;

class QuotationController extends AbstractController
{
    public function listQuotationAction()
    {
        return $this->render('quotation/Quotation/listQuotation.html.twig');
    }
	
	public function listQuotationDatatablesAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$language = $request->getLocale();

		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $language);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $language, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Biography_Show', array('id' => $entity->getAuthorQuotation()->getId(), 'title' => $entity->getAuthorQuotation()->getTitle()))."'><i> ".$entity->getAuthorQuotation()."</a>";
			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }
	
	public function readQuotationAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Quotation::class)->find($id);
		
		return $this->render("quotation/Quotation/readQuotation.html.twig", array('entity' => $entity));
	}
	
	public function quotationsServerSideAction(Request $request, PaginatorInterface $paginator, $authorId, $page)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $request->getLocale();
		$biography = $em->getRepository(Biography::class)->find($authorId);
		
		$query = $em->getRepository(Quotation::class)->getQuotationsByAuthor($biography, $language);
		
		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			30 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);
		
        return $this->render('quotation/Quotation/quotationsByAuthor.html.twig', array(
			'pagination' => $pagination
		));
	}
	
    public function randomQuoteAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Quotation::class)->randomQuote($request->getLocale());
		
        return $this->render('quotation/Quotation/randomQuote.html.twig', array(
			"entity" => $entity,
		));
    }
	
	/* FONCTION DE COMPTAGE */
	public function countQuotationAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$nbrTotalQuotation = $em->getRepository(Quotation::class)->countCitation($request->getLocale());
		return new Response($nbrTotalQuotation);
	}
}