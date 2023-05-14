<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Quotation;
use App\Entity\Biography;
use App\Entity\Country;
use Knp\Component\Pager\PaginatorInterface;

class QuotationController extends AbstractController
{
    public function listQuotationAction()
    {
        return $this->render('quotation/Quotation/listQuotation.html.twig');
    }
	
	public function listQuotationDatatablesAction(Request $request, $family)
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

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $family, $language);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $family, $language, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<i>".$entity->getTextQuotation()."</i>";

			if($entity->isQuotationFamily())
				$row[] = "<a href='".$this->generateUrl('Biography_Show', ['id' => $entity->getAuthorQuotation()->getId(), 'title' => $entity->getAuthorQuotation()->getTitle()])."'>".$entity->getAuthorQuotation()."</a>";
			else {
				$flag = '<img src="'.$request->getBasePath().'/'.$entity->getCountry()->getAssetImagePath().$entity->getCountry()->getFlag().'" alt="" width="20px" height="13px">';
				$row[] = "$flag <a href='".$this->generateUrl('Proverb_Country_Show', ['id' => $entity->getCountry()->getId(), 'title' => $entity->getCountry()->getTitle()])."'>".$entity->getCountry()."</a>";
			}

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }

	public function proverbCountryAction($id) {
		$em = $this->getDoctrine()->getManager();
		$country = $em->getRepository(Country::class)->find($id);

		return $this->render('quotation/Quotation/listProverbByCountry.html.twig', ["country" => $country]);
	}

	public function listProverbByCountryDatatablesAction(Request $request, $countryId) {
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

        $entities = $em->getRepository(Quotation::class)->getProverbDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language);
		$iTotal = $em->getRepository(Quotation::class)->getProverbDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Quotation_ReadQuotation', ['id' => $entity->getId()])."'>Read</a>";

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
		
		return $this->render("quotation/Quotation/readQuotation.html.twig", ['entity' => $entity]);
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
		
        return $this->render('quotation/Quotation/quotationsByAuthor.html.twig', [
			'pagination' => $pagination
		]);
	}

    public function randomQuoteAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Quotation::class)->randomQuote($request->getLocale());
		
        return $this->render('quotation/Quotation/randomQuote.html.twig', [
			"entity" => $entity,
		]);
    }
	
	/* FONCTION DE COMPTAGE */
	public function countQuotationAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$nbrTotalQuotation = $em->getRepository(Quotation::class)->countCitation($request->getLocale());
		return new Response($nbrTotalQuotation);
	}
}