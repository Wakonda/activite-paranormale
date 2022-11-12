<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Book;
use App\Entity\BookEdition;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\Publisher;
use App\Entity\LiteraryGenre;
use App\Form\Type\BookSearchType;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\APImgSize;
use App\Service\APDate;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BookController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page)
    {
		$em = $this->getDoctrine()->getManager();

		$nbMessageByPage = 12;

		$form = $this->createForm(BookSearchType::class, null, ["locale" => $request->getLocale()]);
		$form->handleRequest($request);
		$datas = null;

		if ($form->isSubmitted() && $form->isValid())
			$datas = $form->getData();

		$query = $em->getRepository(Book::class)->getBooks($datas, $nbMessageByPage, $page, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('book/Book/index.html.twig', ['pagination' => $pagination, 'form' => $form->createView()]);
    }
	
	public function showAction($id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Book::class)->find($id);

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		return $this->render('book/Book/show.html.twig', ['entity' => $entity]);
	}

	public function byPublisherAction(Request $request, PaginatorInterface $paginator, $idPublisher, $titlePublisher, $page)
	{
		$em = $this->getDoctrine()->getManager();
		$nbMessageByPage = 12;
		$publisher = $em->getRepository(Publisher::class)->find($idPublisher);
		$query = $em->getRepository(BookEdition::class)->getBooksByPublisher($idPublisher, $nbMessageByPage, $page);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);
	
		return $this->render("book/Book/byPublisher.html.twig", ['pagination' => $pagination, "publisher" => $publisher]);
	}

	public function byGenreAction(Request $request, PaginatorInterface $paginator, $idGenre, $titleGenre, $page)
	{
		$em = $this->getDoctrine()->getManager();
		$nbMessageByPage = 12;

		$genre = $em->getRepository(LiteraryGenre::class)->find($idGenre);
		$query = $em->getRepository(Book::class)->getBooksByGenre($idGenre, $nbMessageByPage, $page);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);
	
		return $this->render("book/Book/byGenre.html.twig", ['pagination' => $pagination, "genre" => $genre ]);
	}

	// Book of the world
	public function worldAction($language, $themeId, $theme)
	{
		$em = $this->getDoctrine()->getManager();
		$flags = $em->getRepository(Language::class)->displayFlagWithoutWorld();
		$currentLanguage = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $language));

		$themes = $em->getRepository(Theme::class)->getAllThemesWorld(explode(",", $_ENV["LANGUAGES"]));

		$theme = $em->getRepository(Theme::class)->find($themeId);

		$title = [];

		if(!empty($currentLanguage))
			$title[] = $currentLanguage->getTitle();

		if(!empty($theme))
			$title[] = $theme->getTitle();

		return $this->render('book/Book/world.html.twig', array(
			'flags' => $flags,
			'themes' => $themes,
			'title' => implode(" - ", $title),
			'theme' => empty($theme) ? null : $theme
		));
	}

	public function selectThemeForIndexWorldAction(Request $request, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');

		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(Theme::class)->find($themeId);
		return new Response($this->generateUrl('Book_World', array('language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle())));
	}

	public function worldDatatablesAction(Request $request, APImgSize $imgSize, APDate $date, $language)
	{
		$em = $this->getDoctrine()->getManager();
		$themeId = $request->query->get("theme_id");
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
		
        $entities = $em->getRepository(Book::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Book::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function saveAction(int $id, string $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(BookEdition::class)->find($id);
		
		return $this->render('book/Book/save.html.twig', array(
			'entity' => $entity
		));
	}

	public function downloadAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(BookEdition::class)->find($id);

		$response = new Response();
		$response->setContent(file_get_contents($entity->getAssetPdfPath().$entity->getWholeBook()));

		$response->headers->set('Content-type', mime_content_type($entity->getAssetPdfPath().$entity->getPdfTheme()));
		$response->headers->set('Content-Disposition', 'attachment; filename="'.$entity->getPdfTheme().'"');
		$response->headers->set("Content-Transfer-Encoding", "Binary");
		
		return $response;
	}

	/* FONCTION DE COMPTAGE */
	public function countAction($language)
	{
		$em = $this->getDoctrine()->getManager();

		return new Response($em->getRepository(Book::class)->countByLanguage($language));
	}
}