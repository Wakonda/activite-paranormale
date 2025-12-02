<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Book;
use App\Entity\BookEdition;
use App\Entity\Biography;
use App\Entity\BookEditionBiography;
use App\Entity\FileManagement;
use App\Form\Type\BookEditionAdminType;
use App\Service\ConstraintControllerValidator;
use Doctrine\Common\Collections\ArrayCollection;

#[Route('/admin/bookedition')]
class BookEditionAdminController extends AdminGenericController
{
	protected $entityName = 'BookEdition';
	protected $className = BookEdition::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "BookEdition_Admin_Index"; 
	protected $showRoute = "BookEdition_Admin_Show";
	protected $formName = 'ap_book_bookextensionadmintype';
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	protected $illustrationWholeBooks = [["field" => "wholeBook", "selectorFile" => "file_selector"]];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrationWholeBooks);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		foreach ($form->get('biographies') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($em, $entityBindded, $form, "biographies");
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		$originalBiographies = new ArrayCollection($em->getRepository(BookEditionBiography::class)->findBy(["bookEdition" => $entityBindded->getId()]));
		
		foreach($originalBiographies as $originalBiography)
		{
			if(false === $entityBindded->getBiographies()->contains($originalBiography))
			{
				$em->remove($originalBiography);
			}
		}

		if(!empty($entityBindded->getBiographies())) 
		{
			foreach($entityBindded->getBiographies() as $mb)
			{
				if(!empty($mb->getBiography())) {
					$mb->setBookEdition($entityBindded);
					$em->persist($mb);
				}
			}
		}

		$em->flush();
	}

	#[Route('/{bookId}', name: 'BookEdition_Admin_Index', requirements: ['bookId' => "\d+"])]
    public function index(Int $bookId)
    {
		$twig = 'book/BookEditionAdmin/index.html.twig';
		return $this->render($twig, ["bookId" => $bookId]);
    }

	#[Route('/{id}/show', name: 'BookEdition_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'book/BookEditionAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'BookEdition_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em, Int $bookId)
    {
		$formType = BookEditionAdminType::class;
		$entity = new BookEdition();
		$book = $em->getRepository(Book::class)->find($bookId);
		
		$entity->setBook($book);

		$twig = 'book/BookEditionAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $book->getLanguage()->getAbbreviation()]);
    }

	#[Route('/create', name: 'BookEdition_Admin_Create', requirements: ['_method' => "post"])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, Int $bookId)
    {
		$formType = BookEditionAdminType::class;
		$entity = new BookEdition();
		$book = $em->getRepository(Book::class)->find($bookId);
		
		$entity->setBook($book);

		$twig = 'book/BookEditionAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ["locale" => $book->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/edit', name: 'BookEdition_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(BookEdition::class)->find($id);
		$formType = BookEditionAdminType::class;

		$twig = 'book/BookEditionAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ["locale" => $entity->getBook()->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'BookEdition_Admin_Delete', requirements: ['_method' => "post"])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$entity = $em->getRepository(BookEdition::class)->find($id);
		$formType = BookEditionAdminType::class;
		$twig = 'book/BookEditionAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ["locale" => $entity->getBook()->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/delete', name: 'Book_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'BookEdition_Admin_IndexDatatables', requirements: ['_method' => "get"])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, Int $bookId)
	{
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $bookId);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $bookId, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getPublisher()->getTitle();
			$row[] = $entity->getIsbn10();
			$row[] = $entity->getIsbn13();
			$row[] = "
			 <a href='".$this->generateUrl('BookEdition_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('BookEdition_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/reload_theme_by_language', name: 'BookEdition_Admin_ReloadThemeByLanguage')]
	public function reloadThemeByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language))
		{
			$genres = $em->getRepository(GenreAudiovisual::class)->findByLanguage($language, array('title' => 'ASC'));
			$countries = $em->getRepository(Region::class)->findByLanguage($language, array('title' => 'ASC'));
		}
		else
		{
			$genres = $em->getRepository(GenreAudiovisual::class)->findAll();
			$countries = $em->getRepository(Region::class)->findAll();
		}

		$genreArray = [];
		
		foreach($genres as $genre)
			$genreArray[] = array("id" => $genre->getId(), "title" => $genre->getTitle());

		$translateArray['genre'] = $genreArray;

		$countryArray = [];
		
		foreach($countries as $country)
			$countryArray[] = array("id" => $country->getId(), "title" => $country->getTitle());

		$translateArray['country'] = $countryArray;

		return new JsonResponse($translateArray);
	}

	#[Route('/google_book', name: 'BookEdition_Admin_GoogleBook')]
	public function googleBook(Request $request, EntityManagerInterface $em, \App\Service\GoogleBook $googleBook)
	{
		$isbn = $request->query->get("isbn");
		
		$res = $googleBook->getBookInfoByISBN(strval($isbn));

		return new JsonResponse($res);
	}
}