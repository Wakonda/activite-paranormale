<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Book;
use App\Entity\Language;
use App\Entity\Biography;
use App\Entity\BookTags;
use App\Entity\LiteraryGenre;
use App\Entity\Theme;
use App\Entity\State;
use App\Form\Type\BookAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

/**
 * Book controller.
 *
 */
class BookAdminController extends AdminGenericController
{
	protected $entityName = 'Book';
	protected $className = Book::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Book_Admin_Index"; 
	protected $showRoute = "Book_Admin_Show";
	protected $formName = 'ap_book_bookadmintype';

	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));

		foreach ($form->get('authors') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', array(), 'validators')));

		foreach ($form->get('fictionalCharacters') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', array(), 'validators')));

		if($form->isValid()) {
			$this->saveNewBiographies($entityBindded, $form, "authors", false);
			$this->saveNewBiographies($entityBindded, $form, "fictionalCharacters", false);
		}
	}

	public function postValidationAction($form, $entityBindded)
	{
		(new TagsManagingGeneric($this->getDoctrine()->getManager()))->saveTags($form, $this->className, $this->entityName, new BookTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'book/BookAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'book/BookAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = BookAdminType::class;
		$entity = new Book();

		$twig = 'book/BookAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = BookAdminType::class;
		$entity = new Book();

		$twig = 'book/BookAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository(Book::class)->find($id);
		$formType = BookAdminType::class;

		$twig = 'book/BookAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = BookAdminType::class;
		$twig = 'book/BookAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		$em = $this->getDoctrine()->getManager();
		$votes = $em->getRepository("\App\Entity\BookVote")->findBy(["book" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\BookTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($id);
    }
	
	public function archiveAction($id)
	{
		$additionalFiles = [];
		
		$entity = $em->getRepository($this->className)->find($id);
		
		foreach($entity->getBookEditions() as $fm) {
			$additionalFiles[] = $fm->getRealNameFile();
		}

		return $this->archiveGenericArchive($id, $additionalFiles);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Book_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = array();
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Book_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Book_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];

		$literaryGenreArray = [];
		
		if(!empty($language)) {
			$literaryGenres = $em->getRepository(LiteraryGenre::class)->findByLanguage($language, ['title' => 'ASC']);
			$themes = $em->getRepository(Theme::class)->getByLanguageForList($language->getAbbreviation(), $request->getLocale());
			$states = $em->getRepository(State::class)->findByLanguage($language, array('title' => 'ASC'));
		}
		else {
			$literaryGenres = $em->getRepository(LiteraryGenre::class)->findAll();
			$themes = $em->getRepository(Theme::class)->getByLanguageForList(null, $request->getLocale());
			$states = $em->getRepository(State::class)->findAll();
		}

		$themeArray = [];
		$stateArray = [];
		
		foreach($themes as $theme)
			$themeArray[] = ["id" => $theme["id"], "title" => $theme["title"]];

		$translateArray['theme'] = $themeArray;

		foreach($states as $state)
			$stateArray[] = array("id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName());

		$translateArray['state'] = $stateArray;
		
		foreach($literaryGenres as $literaryGenre)
			$literaryGenreArray[] = ["id" => $literaryGenre->getId(), "title" => $literaryGenre->getTitle()];

		$translateArray['literaryGenre'] = $literaryGenreArray;

		return new JsonResponse($translateArray);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = BookAdminType::class;
		$entity = new Book();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(Book::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		
		$state = null;
		
		if(!empty($entityToCopy->getState()))
			$state = $em->getRepository(State::class)->findOneBy(["internationalName" => $entityToCopy->getState()->getInternationalName(), "language" => $language]);
		
		$licence = null;
		
		if(!empty($entityToCopy->getLicence())) {
			$licence = $em->getRepository(Licence::class)->findOneBy(["internationalName" => $entityToCopy->getLicence()->getInternationalName(), "language" => $language]);
			
			$entity->setLicence($licence);
		}
		
		$theme = null;
		
		if(!empty($entityToCopy->getTheme()))
			$theme = $em->getRepository(Theme::class)->findOneBy(["internationalName" => $entityToCopy->getTheme()->getInternationalName(), "language" => $language]);
		
		$genre = null;
		
		if(!empty($entityToCopy->getGenre()))
			$genre = $em->getRepository(LiteraryGenre::class)->findOneBy(["internationalName" => $entityToCopy->getGenre()->getInternationalName(), "language" => $language]);
		
		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setWikidata($entityToCopy->getWikidata());
		$entity->setWritingDate($entityToCopy->getWritingDate());
		$entity->setPublicationDate($entityToCopy->getPublicationDate());
		$entity->setLanguage($language);
		$entity->setState($state);
		$entity->setTheme($theme);
		$entity->setGenre($genre);

		$mbArray = new \Doctrine\Common\Collections\ArrayCollection();
		
		foreach($entityToCopy->getAuthors() as $mbToCopy) {
			$biography = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $mbToCopy->getInternationalName(), "language" => $language]);
			
			if(empty($biography))
				continue;
			
			$entity->addAuthor($biography);
			$mbArray->add($biography);
		}
		
		$entity->setAuthors($mbArray);

		$mbArray = new \Doctrine\Common\Collections\ArrayCollection();
		
		foreach($entityToCopy->getFictionalCharacters() as $mbToCopy) {
			$biography = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $mbToCopy->getInternationalName(), "language" => $language]);
			
			if(empty($biography))
				continue;
			
			$entity->addFictionalCharacter($biography);
			$mbArray->add($biography);
		}
		
		$entity->setFictionalCharacters($mbArray);

		if(!empty($wikicode = $entityToCopy->getWikidata())) {
			$wikidata = new \App\Service\Wikidata($em);
			$data = $wikidata->getTitleAndUrl($wikicode, $language->getAbbreviation());
			
			if(!empty($data))
			{
				$sourceArray = [[
					"author" => null,
					"url" => $data["url"],
					"type" => "url",
				]];
				
				$entity->setSource(json_encode($sourceArray));
				
				if(!empty($title = $data["title"]))
					$entity->setTitle($title);
			}
		}

		if(!empty($ci = $entityToCopy->getIllustration())) {
			$illustration = new FileManagement();
			$illustration->setTitleFile($ci->getTitleFile());
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());

			$entity->setIllustration($illustration);
		}

		$twig = 'book/BookAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }
}