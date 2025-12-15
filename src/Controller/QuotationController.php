<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Quotation;
use App\Entity\Biography;
use App\Entity\Region;
use App\Form\Type\QuotationSearchType;
use Knp\Component\Pager\PaginatorInterface;

class QuotationController extends AbstractController
{
	#[Route('/quotation/index/{family}', name: 'Quotation_Index', defaults: ['family' => null])]
    public function index(Request $request, EntityManagerInterface $em, $family)
    {
		$counter = $em->getRepository(Quotation::class)->countByFamily($request->getLocale());
		$family = empty($family) ? Quotation::QUOTATION_FAMILY : $family;
        return $this->render('quotation/Quotation/index.html.twig', ["family" => $family, "quotation_counter" => $counter]);
    }

    public function listQuotation(Request $request)
    {
		$form = $this->createForm(QuotationSearchType::class, [], ["locale" => $request->getLocale(), "family" => Quotation::QUOTATION_FAMILY]);
        return $this->render('quotation/Quotation/listQuotation.html.twig', ["family" => Quotation::QUOTATION_FAMILY, "form" => $form->createView()]);
    }

    public function listProverb(Request $request)
    {
		$form = $this->createForm(QuotationSearchType::class, [], ["locale" => $request->getLocale(), "family" => Quotation::PROVERB_FAMILY]);
        return $this->render('quotation/Quotation/listProverb.html.twig', ["family" => Quotation::PROVERB_FAMILY, "form" => $form->createView()]);
    }

    public function listPoem(Request $request)
    {
		$form = $this->createForm(QuotationSearchType::class, [], ["locale" => $request->getLocale(), "family" => Quotation::POEM_FAMILY]);
        return $this->render('quotation/Quotation/listPoem.html.twig', ["family" => Quotation::POEM_FAMILY, "form" => $form->createView()]);
    }

    public function listHumor(Request $request)
    {
		$form = $this->createForm(QuotationSearchType::class, [], ["locale" => $request->getLocale(), "family" => Quotation::HUMOR_FAMILY]);
        return $this->render('quotation/Quotation/listHumor.html.twig', ["family" => Quotation::HUMOR_FAMILY, "form" => $form->createView()]);
    }

    public function listSaying(Request $request)
    {
		$form = $this->createForm(QuotationSearchType::class, [], ["locale" => $request->getLocale(), "family" => Quotation::SAYING_FAMILY]);
        return $this->render('quotation/Quotation/listSaying.html.twig', ["family" => Quotation::SAYING_FAMILY, "form" => $form->createView()]);
    }

    public function listLyric(Request $request)
    {
		$form = $this->createForm(QuotationSearchType::class, [], ["locale" => $request->getLocale(), "family" => Quotation::LYRIC_FAMILY]);
        return $this->render('quotation/Quotation/listLyric.html.twig', ["family" => Quotation::LYRIC_FAMILY, "form" => $form->createView()]);
    }

	#[Route('/listhumordatatables', name: 'Humor_listDatatables')]
	public function listHumorDatatables(Request $request, EntityManagerInterface $em)
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

		$form = $this->createForm(QuotationSearchType::class, null, ["locale" => $request->getLocale(), "family" => Quotation::HUMOR_FAMILY]);
		parse_str($request->query->get($form->getName()), $datas);
		$form->submit($datas[$form->getName()]);

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::HUMOR_FAMILY, $language, $datas);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::HUMOR_FAMILY, $language, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Humor_Read', ['id' => $entity->getId()])."' class='btn btn-info btn-sm'><i class='fa-solid fa-info fa-fw'></i></a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }

	#[Route('/listquotationdatatables', name: 'Quotation_listDatatables')]
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

		$form = $this->createForm(QuotationSearchType::class, null, ["locale" => $request->getLocale(), "family" => Quotation::QUOTATION_FAMILY]);
		parse_str($request->query->get($form->getName()), $datas);
		$form->submit($datas[$form->getName()]);

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::QUOTATION_FAMILY, $language, $datas);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::QUOTATION_FAMILY, $language, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Biography_Show', ['id' => $entity->getAuthorQuotation()->getId(), 'title_slug' => $entity->getAuthorQuotation()->getSlug()])."'>".$entity->getAuthorQuotation()."</a>";
			$row[] = "<a href='".$this->generateUrl('Quotation_Read', ['id' => $entity->getId()])."' class='btn btn-info btn-sm'><i class='fa-solid fa-info fa-fw'></i></a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }

	#[Route('/listlyricdatatables', name: 'Lyric_listDatatables')]
	public function listLyricDatatables(Request $request, EntityManagerInterface $em)
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

		$form = $this->createForm(QuotationSearchType::class, null, ["locale" => $request->getLocale(), "family" => Quotation::LYRIC_FAMILY]);
		parse_str($request->query->get($form->getName()), $datas);
		$form->submit($datas[$form->getName()]);

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::LYRIC_FAMILY, $language, $datas);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::LYRIC_FAMILY, $language, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$artist = (!empty($entity->getMusic()->getAlbum()) ? $entity->getMusic()->getAlbum()->getArtist() : $entity->getMusic()->getArtist());
			// $album = $entity->getMusic()->getAlbum();
			
			$row = [];
			$row[] = $entity->getId();
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Music_Music', ['id' => $entity->getMusic()->getId(), 'title_slug' => $entity->getMusic()->getUrlSlug()])."'>".$entity->getMusic()->getMusicPiece()."</a>";
			$row[] = "<a href='".$this->generateUrl('Music_Album', ['id' => $artist->getId(), 'title_slug' => $artist->getUrlSlug()])."'>".$artist->getTitle()."</a>";
			$row[] = "<a href='".$this->generateUrl('Lyric_Read', ['id' => $entity->getId()])."' class='btn btn-info btn-sm'><i class='fa-solid fa-info fa-fw'></i></a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }

	#[Route('/listproverbdatatables', name: 'Proverb_listDatatables')]
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

		$form = $this->createForm(QuotationSearchType::class, null, ["locale" => $request->getLocale(), "family" => Quotation::PROVERB_FAMILY]);
		parse_str($request->query->get($form->getName()), $datas);
		$form->submit($datas[$form->getName()]);

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::PROVERB_FAMILY, $language, $datas);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::PROVERB_FAMILY, $language, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$flag = '<img src="'.$request->getBasePath().'/'.$entity->getCountry()->getAssetImagePath().$entity->getCountry()->getFlag().'" alt="'.addslashes($entity->getCountry()->getTitle()).'" width="20" height="13">';
			$row[] = "$flag <a href='".$this->generateUrl('Proverb_Country_Show', ['id' => $entity->getCountry()->getId(), 'title' => $entity->getCountry()->getTitle()])."'>".$entity->getCountry()."</a>";
			$row[] = "<a href='".$this->generateUrl('Proverb_Read', ['id' => $entity->getId()])."' class='btn btn-info btn-sm'><i class='fa-solid fa-info fa-fw'></i></a>";
			
			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }

	#[Route('/listsayingdatatables', name: 'Saying_listDatatables')]
	public function listSayingDatatables(Request $request, EntityManagerInterface $em)
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

		$form = $this->createForm(QuotationSearchType::class, null, ["locale" => $request->getLocale(), "family" => Quotation::SAYING_FAMILY]);
		parse_str($request->query->get($form->getName()), $datas);
		$form->submit($datas[$form->getName()]);

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::SAYING_FAMILY, $language, $datas);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::SAYING_FAMILY, $language, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$flag = '<img src="'.$request->getBasePath().'/'.$entity->getCountry()->getAssetImagePath().$entity->getCountry()->getFlag().'" alt="'.addslashes($entity->getCountry()->getTitle()).'" width="20" height="13">';
			$row[] = "$flag <a href='".$this->generateUrl('Saying_Country_Show', ['id' => $entity->getCountry()->getId(), 'title' => $entity->getCountry()->getTitle()])."'>".$entity->getCountry()."</a>";
			$row[] = "<a href='".$this->generateUrl('Saying_Read', ['id' => $entity->getId()])."' class='btn btn-info btn-sm'><i class='fa-solid fa-info fa-fw'></i></a>";
			
			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }

	#[Route('/listpoemdatatables', name: 'Poem_listDatatables')]
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

		$form = $this->createForm(QuotationSearchType::class, null, ["locale" => $request->getLocale(), "family" => Quotation::POEM_FAMILY]);
		parse_str($request->query->get($form->getName()), $datas);
		$form->submit($datas[$form->getName()]);

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];

        $entities = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::POEM_FAMILY, $language, $datas);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, Quotation::POEM_FAMILY, $language, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = "<a href='".$this->generateUrl('Biography_Show', ['id' => $entity->getAuthorQuotation()->getId(), 'title_slug' => $entity->getAuthorQuotation()->getSlug()])."'>".$entity->getAuthorQuotation()."</a>";
			$row[] = "<a href='".$this->generateUrl('Poem_Read', ['id' => $entity->getId()])."' class='btn btn-info btn-sm'><i class='fa-solid fa-info fa-fw'></i></a>";
			
			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }

	#[Route('/quotation/proverb/{id}/{title}', name: 'Proverb_Country_Show', requirements: ['id' => '\d+'])]
	public function proverbCountry(EntityManagerInterface $em, $id) {
		$country = $em->getRepository(Region::class)->find($id);

		return $this->render('quotation/Quotation/listProverbByCountry.html.twig', ["country" => $country]);
	}

	#[Route('/listproverbbycountrydatatables/{countryId}', name: 'Proverb_listProverbByCountryDatatables', requirements: ['countryId' => '\d+'])]
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

        $entities = $em->getRepository(Quotation::class)->getDatatablesByCountryForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language, Quotation::PROVERB_FAMILY);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesByCountryForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language, Quotation::PROVERB_FAMILY, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Proverb_Read', ['id' => $entity->getId()])."' class='btn btn-info btn-sm'><i class='fa-solid fa-info fa-fw'></i></a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	#[Route('/quotation/saying/{id}/{title}', name: 'Saying_Country_Show', requirements: ['id' => '\d+'])]
	public function sayingCountry(EntityManagerInterface $em, $id) {
		$country = $em->getRepository(Region::class)->find($id);

		return $this->render('quotation/Quotation/listSayingByCountry.html.twig', ["country" => $country]);
	}

	#[Route('/listsayingbycountrydatatables/{countryId}', name: 'Saying_listSayingByCountryDatatables', requirements: ['countryId' => '\d+'])]
	public function listSayingByCountryDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $countryId) {
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

        $entities = $em->getRepository(Quotation::class)->getDatatablesByCountryForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language, Quotation::SAYING_FAMILY);
		$iTotal = $em->getRepository(Quotation::class)->getDatatablesByCountryForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language, Quotation::SAYING_FAMILY, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = "<i>".$entity->getTextQuotation()."</i>";
			$row[] = "<a href='".$this->generateUrl('Saying_Read', ['id' => $entity->getId()])."' class='btn btn-info btn-sm'><i class='fa-solid fa-info fa-fw'></i></a>";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	#[Route('/proverb/read/{id}', name: 'Proverb_Read', requirements: ['id' => '\d+'])]
	public function readProverb(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readProverb.html.twig", ['entity' => $entity]);
	}

	#[Route('/saying/read/{id}', name: 'Saying_Read', requirements: ['id' => '\d+'])]
	public function readSaying(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readSaying.html.twig", ['entity' => $entity]);
	}

	#[Route('/humor/read/{id}', name: 'Humor_Read', requirements: ['id' => '\d+'])]
	public function readHumor(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readHumor.html.twig", ['entity' => $entity]);
	}

	#[Route('/quotation/read/{id}', name: 'Quotation_Read', requirements: ['id' => '\d+'])]
	public function readQuotation(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readQuotation.html.twig", ['entity' => $entity]);
	}

	#[Route('/poem/read/{id}', name: 'Poem_Read', requirements: ['id' => '\d+'])]
	public function readPoem(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readPoem.html.twig", ['entity' => $entity]);
	}

	#[Route('/lyric/read/{id}', name: 'Lyric_Read', requirements: ['id' => '\d+'])]
	public function readLyric(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		return $this->render("quotation/Quotation/readLyric.html.twig", ['entity' => $entity]);
	}

	#[Route('/quotation_server_side/{authorId}/{page}', name: 'Quotation_quotationsServerSide', defaults: ['page' => 1], requirements: ['authorId' => '\d+'])]
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
		return new Response(json_encode($em->getRepository(Quotation::class)->countByFamily($request->getLocale())));
	}
}