<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
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

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		foreach ($form->get('authors') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

		foreach ($form->get('fictionalCharacters') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

		if($form->isValid()) {
			$this->saveNewBiographies($em, $entityBindded, $form, "authors", false);
			$this->saveNewBiographies($em, $entityBindded, $form, "fictionalCharacters", false);
		}
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new BookTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'book/BookAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'book/BookAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = BookAdminType::class;
		$entity = new Book();

		$twig = 'book/BookAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = BookAdminType::class;
		$entity = new Book();

		$twig = 'book/BookAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Book::class)->find($id);
		$formType = BookAdminType::class;

		$twig = 'book/BookAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = BookAdminType::class;
		$twig = 'book/BookAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$votes = $em->getRepository("\App\Entity\BookVote")->findBy(["book" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\BookTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($em, $id);
    }
	
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		$additionalFiles = [];

		$entity = $em->getRepository($this->className)->find($id);
		
		foreach($entity->getBookEditions() as $fm)
			$additionalFiles[] = $fm->getIllustration()->getRealNameFile();

		return $this->archiveGenericArchive($em, $id, $additionalFiles);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Book_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Book_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Book_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadByLanguageAction(Request $request, EntityManagerInterface $em)
	{
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
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = BookAdminType::class;
		$entity = new Book();

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
			
			if(!empty($data) and !empty($data["url"]))
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
			$illustration->setRealNameFile($ci->getRealNameFile());
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());

			$entity->setIllustration($illustration);
		}

		$twig = 'book/BookAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }
}