<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Movies\Movie;
use App\Entity\MovieTags;
use App\Entity\Biography;
use App\Entity\Movies\MovieBiography;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Region;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\FileManagement;
use App\Form\Type\MovieAdminType;
use App\Service\ConstraintControllerValidator;
use Doctrine\Common\Collections\ArrayCollection;
use App\Service\TagsManagingGeneric;
use App\Service\FunctionsLibrary;

#[Route('/admin/movie')]
class MovieAdminController extends AdminGenericController
{
	protected $entityName = 'Movie';
	protected $className = Movie::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Movie_Admin_Index"; 
	protected $showRoute = "Movie_Admin_Show";
	protected $formName = 'ap_movie_movieadmintype';
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		foreach ($form->get('movieBiographies') as $formChild)
		{
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

			if($formChild->get("occupation")->getData() == MovieBiography::ACTOR_OCCUPATION and empty($formChild->get("occupation")->getData()))
				$formChild->get('role')->addError(new FormError($translator->trans("admin.error.NotBlank", [], "validators")));
		}

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($em, $entityBindded, $form, "movieBiographies");
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		$originalMovieBiographies = new ArrayCollection($em->getRepository(MovieBiography::class)->findBy(["movie" => $entityBindded->getId()]));

		foreach($originalMovieBiographies as $originalMovieBiography)
		{
			if(false === $entityBindded->getMovieBiographies()->contains($originalMovieBiography))
			{
				$em->remove($originalMovieBiography);
			}
		}

		foreach($entityBindded->getMovieBiographies() as $mb)
		{
			if(!empty($mb->getBiography())) {
				$mb->setMovie($entityBindded);
				$em->persist($mb);	
			}
		}

		$em->flush();

		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new MovieTags(), $entityBindded);
	}

	#[Route('/', name: 'Movie_Admin_Index')]
    public function index()
    {
		$twig = 'movie/MovieAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Movie_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'movie/MovieAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'Movie_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = MovieAdminType::class;
		$entity = new Movie();

		$twig = 'movie/MovieAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'Movie_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = MovieAdminType::class;
		$entity = new Movie();

		$twig = 'movie/MovieAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'Movie_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Movie::class)->find($id);
		$formType = MovieAdminType::class;

		$twig = 'movie/MovieAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'Movie_Admin_Update', methods: ['POST'])]
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = MovieAdminType::class;
		$twig = 'movie/MovieAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'Movie_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\MovieComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\MovieVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\MovieTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }

	#[Route('/archive/{id}', name: 'Movie_Admin_Archive', requirements: ['id' => '\d+'])]
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
	}

	#[Route('/datatables', name: 'Movie_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Movie_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Movie_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/showImageSelectorColorbox', name: 'Movie_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('Movie_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'Movie_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	#[Route('/reload_theme_by_language', name: 'Movie_Admin_ReloadThemeByLanguage')]
	public function reloadThemeByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];

		if(!empty($language))
		{
			$genres = $em->getRepository(GenreAudiovisual::class)->findByLanguage($language, ['title' => 'ASC']);
			$countries = $em->getRepository(Region::class)->findByLanguage($language, ['title' => 'ASC']);
		}
		else
		{
			$genres = $em->getRepository(GenreAudiovisual::class)->findAll();
			$countries = $em->getRepository(Region::class)->findAll();
		}

		$genreArray = [];

		foreach($genres as $genre)
			$genreArray[] = ["id" => $genre->getId(), "title" => $genre->getTitle()];

		$translateArray['genre'] = $genreArray;

		$countryArray = [];

		foreach($countries as $country)
			$countryArray[] = ["id" => $country->getId(), "title" => $country->getTitle()];

		$translateArray['country'] = $countryArray;

		return new JsonResponse($translateArray);
	}

	#[Route('/autocomplete', name: 'Movie_Admin_Autocomplete')]
	public function autocomplete(Request $request, EntityManagerInterface $em)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		$id = $request->query->get("id", null);

		if(is_numeric($locale)) {
			$language = $em->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}

		$datas =  $em->getRepository(Movie::class)->getAutocomplete($locale, $query, $id);

		$results = [];

		foreach($datas as $data)
		{
			$obj = new \stdClass();
			$obj->id = $data->getId();
			$obj->text = $data->getTitle();

			$results[] = $obj;
		}

        return new JsonResponse(["results" => $results]);
	}

	#[Route('/internationalization/{id}', name: 'Movie_Admin_Internationalization')]
    public function internationalization(Request $request, EntityManagerInterface $em, FunctionsLibrary $functionsLibrary, $id)
    {
		$formType = MovieAdminType::class;
		$entity = new Movie();

		$entityToCopy = $em->getRepository(Movie::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$country = null;

		if(!empty($entityToCopy->getCountry()))
			$country = $em->getRepository(Region::class)->findOneBy(["internationalName" => $entityToCopy->getCountry()->getInternationalName(), "language" => $language]);

		$entity->setCountry($country);

		$previous = null;

		if(!empty($entityToCopy->getPrevious()))
			$previous = $em->getRepository(Movie::class)->findOneBy(["internationalName" => $entityToCopy->getPrevious()->getInternationalName(), "language" => $language]);

		$entity->setPrevious($previous);

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
		$entity->setWikidata($entityToCopy->getWikidata());
		$entity->setFullStreaming($entityToCopy->getFullStreaming());
		$entity->setCost($entityToCopy->getCost());
		$entity->setCostUnit($entityToCopy->getCostUnit());
		$entity->setBoxOffice($entityToCopy->getBoxOffice());
		$entity->setBoxOfficeUnit($entityToCopy->getBoxOfficeUnit());
		$entity->setReviewScores($entityToCopy->getReviewScores());
		$entity->setSocialNetworkIdentifiers($entityToCopy->getSocialNetworkIdentifiers());
		$entity->setIdentifiers($entityToCopy->getIdentifiers());

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

		$mbArray = new \Doctrine\Common\Collections\ArrayCollection();

		foreach($entityToCopy->getMovieBiographies() as $mbToCopy) {
			$mb = new MovieBiography();

			$biography = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $mbToCopy->getBiography()->getInternationalName(), "language" => $language]);

			if(empty($biography)) {
				$biography = $em->getRepository(Biography::class)->findOneBy(["wikidata" => $mbToCopy->getBiography()->getWikidata()]);
			
				if(empty($biography))
					continue;
				
				$newBiography = $functionsLibrary->copyBiography($mbToCopy->getBiography(), $language);

				$em->persist($newBiography);
				$em->flush();

				$biography = $newBiography;
			}
			$mb->setRole($mbToCopy->getRole());
			$mb->setOccupation($mbToCopy->getOccupation());
			$mb->setMovie($entity);
			$mb->setBiography($biography);
			
			$mbArray->add($mb);
		}

		$entity->setMovieBiographies($mbArray);

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

		$request->setLocale($language->getAbbreviation());

		$twig = 'movie/MovieAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	#[Route('/wikidata', name: 'Movie_Admin_Wikidata')]
	public function wikidataAction(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");

		$res = $wikidata->getMovieDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
}