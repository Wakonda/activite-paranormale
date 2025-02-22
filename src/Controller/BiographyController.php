<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Biography;
use App\Entity\Quotation;
use App\Entity\Document;
use App\Entity\Book;
use App\Entity\BookEditionBiography;
use App\Entity\Language;
use App\Entity\Video;
use App\Service\APImgSize;
use App\Form\Type\BiographySearchType;

class BiographyController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em)
    {
		$datas = [];
		
		if($request->query->has("country") and !empty($c = $request->query->get("country")))
			$datas["country"] = $em->getRepository(\App\Entity\Region::class)->find($c);

		$entities = $em->getRepository(Biography::class)->getBiographies($request->getLocale());
		$form = $this->createForm(BiographySearchType::class, $datas, ["locale" => $request->getLocale()]);
		
        return $this->render('quotation/Biography/index.html.twig', ["entities" => $entities, "form" => $form->createView()]);
    }

	public function readAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Biography::class)->find($id);

		if(empty($entity))
			throw new NotFoundHttpException();
		
		$documents = $em->getRepository(Document::class)->getDocumentsByBiographyInternationalName($entity->getInternationalName());
		$books = $em->getRepository(Book::class)->getBooksByBiographyInternationalName($entity->getInternationalName());
		$bookEditions = $em->getRepository(BookEditionBiography::class)->getBookEditionByBiography($entity);
		$quotationsByAuthor = $em->getRepository(Quotation::class)->findBy(['authorQuotation' => $entity]);
		$videos = $em->getRepository(Video::class)->findBy(['biography' => $entity]);

		return $this->render('quotation/Biography/read.html.twig', [
			'entity' => $entity,
			'quotationsByAuthor' => $quotationsByAuthor,
			'documents' => $documents,
			'books' => $books,
			'videos' => $videos,
			'bookEditions' => $bookEditions
		]);	
	}

	public function listDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize)
	{
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++) {
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}
		
		$form = $this->createForm(BiographySearchType::class, null, ["locale" => $request->getLocale()]);
		parse_str($request->query->get($form->getName()), $datas);
		$form->submit($datas[$form->getName()]);

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];

        $entities = $em->getRepository(Biography::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $datas);
		$iTotal = $em->getRepository(Biography::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$img = empty($entity->getPhotoIllustrationFilename()) ? null : $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename();
			$img = $imgSize->adaptImageSize(250, $img);

			$row = [];
			$row[] = '<a href="'.$this->generateUrl("Biography_Show", ['id' => $entity->getId(), 'title_slug' => $entity->getSlug()]).'" >'.$entity->getTitle().'</a>';
			$row[] = '<img src="'.$request->getBasePath().'/'.$img[2].'" alt="'.addslashes($entity->getTitle()).'" style="width: '.$img[0].';">';

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	// Biography of the world
	public function worldAction(EntityManagerInterface $em, $language)
	{
		$flags = $em->getRepository(Language::class)->displayFlagWithoutWorld();
		$currentLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $language]);

		$title = [];

		if(!empty($currentLanguage))
			$title[] = $currentLanguage->getTitle();


		return $this->render('quotation/Biography/world.html.twig', [
			'flags' => $flags,
			'title' => implode(" - ", $title)
		]);
	}

	public function worldDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, $language)
	{
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

        $entities = $em->getRepository(Biography::class)->getDatatablesForWorldIndex($language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Biography::class)->getDatatablesForWorldIndex($language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];
		
		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="'.addslashes($entity->getLanguage()->getTitle()).'" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="'.addslashes($entity->getTitle()).'" style="width: '.$photo[0].';">';
			$row[] = '<a href="'.$this->generateUrl("Biography_Show", ['id' => $entity->getId(), 'title_slug' => $entity->getSlug()]).'" >'.$entity->getTitle().'</a>';

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	/* FONCTION DE COMPTAGE */
	public function countAction(Request $request, EntityManagerInterface $em)
	{
		$total = $em->getRepository(Biography::class)->countBiography($request->getLocale());
		return new Response($total);
	}
}