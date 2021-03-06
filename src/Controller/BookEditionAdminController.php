<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Book;
use App\Entity\BookEdition;
use App\Entity\Biography;
use App\Entity\BookEditionBiography;
use App\Entity\FileManagement;
use App\Form\Type\BookEditionAdminType;
use App\Service\ConstraintControllerValidator;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * BookEditionAdmin controller.
 *
 */
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
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));

		foreach ($form->get('biographies') as $formChild)
			if(empty($formChild->get('biography')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', array(), 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($entityBindded, $form, "biographies");
	}

	public function postValidationAction($form, $entityBindded)
	{
		$em = $this->getDoctrine()->getManager();
		$originalBiographies = new ArrayCollection($em->getRepository(BookEditionBiography::class)->findBy(["bookEdition" => $entityBindded->getId()]));
		
		foreach($originalBiographies as $originalBiography)
		{
			if(false === $entityBindded->getBiographies()->contains($originalBiography))
			{
				$em->remove($originalBiography);
			}
		}

		foreach($entityBindded->getBiographies() as $mb)
		{
			if(!empty($mb->getBiography())) {
				$mb->setBookEdition($entityBindded);
				$em->persist($mb);	
			}
		}

		$em->flush();
	}

    public function indexAction(Int $bookId)
    {
		$twig = 'book/BookEditionAdmin/index.html.twig';
		return $this->render($twig, ["bookId" => $bookId]);
    }
	
    public function showAction($id)
    {
		$twig = 'book/BookEditionAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request, Int $bookId)
    {
		$em = $this->getDoctrine()->getManager();
		$formType = BookEditionAdminType::class;
		$entity = new BookEdition();
		
		$entity->setBook($em->getRepository(Book::class)->find($bookId));

		$twig = 'book/BookEditionAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, Int $bookId)
    {
		$formType = BookEditionAdminType::class;
		$entity = new BookEdition();
		
		$em = $this->getDoctrine()->getManager();
		$entity->setBook($em->getRepository(Book::class)->find($bookId));

		$twig = 'book/BookEditionAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(Request $request, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository(BookEdition::class)->find($id);
		$formType = BookEditionAdminType::class;

		$twig = 'book/BookEditionAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = BookEditionAdminType::class;
		$twig = 'book/BookEditionAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType);
    }
	
    public function deleteAction($id)
    {
		return $this->deleteGenericAction($id);
    }

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator, Int $bookId)
	{
		$em = $this->getDoctrine()->getManager();
		
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $bookId);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $bookId, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

		foreach($entities as $entity)
		{
			$row = array();
			$row[] = $entity->getPublisher()->getTitle();
			$row[] = $entity->getIsbn10();
			$row[] = $entity->getIsbn13();
			$row[] = "
			 <a href='".$this->generateUrl('BookEdition_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('BookEdition_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('BookEdition_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}

	public function reloadThemeByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = array();
		
		if(!empty($language))
		{
			$genres = $em->getRepository(GenreAudiovisual::class)->findByLanguage($language, array('title' => 'ASC'));
			$countries = $em->getRepository(Country::class)->findByLanguage($language, array('title' => 'ASC'));
		}
		else
		{
			$genres = $em->getRepository(GenreAudiovisual::class)->findAll();
			$countries = $em->getRepository(Country::class)->findAll();
		}

		$genreArray = array();
		
		foreach($genres as $genre)
			$genreArray[] = array("id" => $genre->getId(), "title" => $genre->getTitle());

		$translateArray['genre'] = $genreArray;

		$countryArray = array();
		
		foreach($countries as $country)
			$countryArray[] = array("id" => $country->getId(), "title" => $country->getTitle());

		$translateArray['country'] = $countryArray;

		return new JsonResponse($translateArray);
	}
	
	public function autocompleteAction(Request $request)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		
		if(is_numeric($locale)) {
			$language = $this->getDoctrine()->getManager()->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}
		
		$datas =  $this->getDoctrine()->getManager()->getRepository(BookEdition::class)->getAutocomplete($locale, $query);
		
		$results = array();
		
		foreach($datas as $data)
		{
			$obj = new \stdClass();
			$obj->id = $data->getId();
			$obj->text = $data->getTitle();
			
			$results[] = $obj;
		}

        return new JsonResponse(["results" => $results]);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = BookEditionAdminType::class;
		$entity = new BookEdition();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(BookEdition::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$country = null;
		
		if(!empty($entityToCopy->getCountry()))
			$country = $em->getRepository(Country::class)->findOneBy(["internationalName" => $entityToCopy->getCountry()->getInternationalName(), "language" => $language]);
		
		$entity->setCountry($country);

		$theme = null;

		if(!empty($entityToCopy->getTheme()))
			$theme = $em->getRepository(Theme::class)->findOneBy(["internationalName" => $entityToCopy->getTheme()->getInternationalName(), "language" => $language]);

		$entity->setTheme($theme);

		if(!empty($entityToCopy->getGenre())) {			
			$genre = $em->getRepository(GenreAudiovisual::class)->findOneBy(["internationalName" => $entityToCopy->getGenre()->getInternationalName(), "language" => $language]);
			
			if(!empty($genre))
				$entity->setGenre($genre);
		}

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setTrailer($entityToCopy->getTrailer());
		$entity->setDuration($entityToCopy->getDuration());
		$entity->setReleaseYear($entityToCopy->getReleaseYear());
		
		if(!empty($ci = $entityToCopy->getIllustration())) {
			$illustration = new FileManagement();
			$illustration->setTitleFile($ci->getTitleFile());
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());
			
			$entity->setIllustration($illustration);
		}

		$request->setLocale($language->getAbbreviation());

		$twig = 'movie/TelevisionSerieAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }
	
	public function googleBookAction(Request $request, \App\Service\GoogleBook $googleBook)
	{
		$em = $this->getDoctrine()->getManager();
		$isbn = $request->query->get("isbn");
		
		$res = $googleBook->getBookInfoByISBN(strval($isbn));
// dd($res);
		return new JsonResponse($res);
	}
}