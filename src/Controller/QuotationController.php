<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Quotation;
use App\Entity\Biography;
use App\Entity\Region;
use Knp\Component\Pager\PaginatorInterface;

class QuotationController extends AbstractController
{
    public function index($family)
    {
		$family = empty($family) ? Quotation::QUOTATION_FAMILY : $family;
        return $this->render('quotation/Quotation/index.html.twig', ["family" => $family]);
    }

    public function listQuotation()
    {
        return $this->render('quotation/Quotation/listQuotation.html.twig', ["family" => Quotation::QUOTATION_FAMILY]);
    }

    public function listProverb()
    {
        return $this->render('quotation/Quotation/listProverb.html.twig', ["family" => Quotation::PROVERB_FAMILY]);
    }

    public function listPoem()
    {
        return $this->render('quotation/Quotation/listPoem.html.twig', ["family" => Quotation::POEM_FAMILY]);
    }
	
	public function listQuotationDatatables(Request $request, EntityManagerInterface $em)
    {
		$language = $request->getLocale();

		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::QUOTATION_FAMILY, $language);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::QUOTATION_FAMILY, $language, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Biography_Show', ['id' => $entity->getAuthorQuotation()->getId(), 'title_slug' => $entity->getAuthorQuotation()->getSlug()])."'>".$entity->getAuthorQuotation()."</a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }
	
	public function listProverbDatatables(Request $request, EntityManagerInterface $em)
    {
		$language = $request->getLocale();

		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::PROVERB_FAMILY, $language);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::PROVERB_FAMILY, $language, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$flag = '<img src="'.$request->getBasePath().'/'.$entity->getCountry()->getAssetImagePath().$entity->getCountry()->getFlag().'" alt="" width="20" height="13">';
			$row[] = "$flag <a href='".$this->generateUrl('Proverb_Country_Show', ['id' => $entity->getCountry()->getId(), 'title' => $entity->getCountry()->getTitle()])."'>".$entity->getCountry()."</a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }
	
	public function listPoemDatatables(Request $request, EntityManagerInterface $em)
    {
		$language = $request->getLocale();

		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::POEM_FAMILY, $language);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::POEM_FAMILY, $language, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<a href='".$this->generateUrl("Poem_Read", ["id" => $entity->getId()])."'>".$entity->getTitle()."</a>";
			$row[] = "<a href='".$this->generateUrl('Biography_Show', ['id' => $entity->getAuthorQuotation()->getId(), 'title_slug' => $entity->getAuthorQuotation()->getSlug()])."'>".$entity->getAuthorQuotation()."</a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }

	public function proverbCountry(EntityManagerInterface $em, $id) {
		$country = $em->getRepository(Region::class)->find($id);

		return $this->render('quotation/Quotation/listProverbByCountry.html.twig', ["country" => $country]);
	}

	public function listProverbByCountryDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $countryId) {
		$language = $request->getLocale();

		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Quotation::class)->getProverbDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language);
		$iTotal = $em->getRepository(Quotation::class)->getProverbDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Proverb_Read', ['id' => $entity->getId()])."'>".$translator->trans("quotation.list.Read", [], 'validators')."</a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function readProverb(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readProverb.html.twig", ['entity' => $entity]);
	}

	public function readQuotation(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readQuotation.html.twig", ['entity' => $entity]);
	}

	public function readPoem(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readPoem.html.twig", ['entity' => $entity]);
	}
	
	public function quotationsServerSide(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $authorId, $page)
	{
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

    public function randomQuote(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Quotation::class)->randomQuote($request->getLocale());

        return $this->render('quotation/Quotation/randomQuote.html.twig', [
			"entity" => $entity,
		]);
    }
	
	/* FONCTION DE COMPTAGE */
	public function countQuotation(Request $request, EntityManagerInterface $em)
	{
		$nbrTotalQuotation = $em->getRepository(Quotation::class)->countCitation($request->getLocale());
		return new Response($nbrTotalQuotation);
	}
}