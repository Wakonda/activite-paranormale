<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Movies\Movie;
use App\Entity\MovieTags;
use App\Entity\Biography;
use App\Entity\Movies\MovieBiography;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Country;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\FileManagement;
use App\Form\Type\MovieAdminType;
use App\Service\ConstraintControllerValidator;
use Doctrine\Common\Collections\ArrayCollection;
use App\Service\TagsManagingGeneric;

/**
 * Movie controller.
 *
 */
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

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		foreach ($form->get('movieBiographies') as $formChild)
		{
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', array(), 'validators')));

			if($formChild->get("occupation")->getData() == MovieBiography::ACTOR_OCCUPATION and empty($formChild->get("occupation")->getData()))
				$formChild->get('role')->addError(new FormError($translator->trans("admin.error.NotBlank", [], "validators")));
		}

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($entityBindded, $form, "movieBiographies");
	}

	public function postValidationAction($form, $entityBindded)
	{
		$em = $this->getDoctrine()->getManager();
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
		
		(new TagsManagingGeneric($this->getDoctrine()->getManager()))->saveTags($form, $this->className, $this->entityName, new MovieTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'movie/MovieAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'movie/MovieAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = MovieAdminType::class;
		$entity = new Movie();

		$twig = 'movie/MovieAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = MovieAdminType::class;
		$entity = new Movie();

		$twig = 'movie/MovieAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository(Movie::class)->find($id);
		$formType = MovieAdminType::class;

		$twig = 'movie/MovieAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = MovieAdminType::class;
		$twig = 'movie/MovieAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository("\App\Entity\MovieComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\MovieVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\MovieTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($id);
    }
	
	public function archiveAction($id)
	{
		return $this->archiveGenericArchive($id);
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
			 <a href='".$this->generateUrl('Movie_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Movie_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Movie_Admin_LoadImageSelectorColorbox');
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
		
		$datas =  $this->getDoctrine()->getManager()->getRepository(Movie::class)->getAutocomplete($locale, $query);
		
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
		$formType = MovieAdminType::class;
		$entity = new Movie();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(Movie::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$country = null;
		
		if(!empty($entityToCopy->getCountry()))
			$country = $em->getRepository(Country::class)->findOneBy(["internationalName" => $entityToCopy->getCountry()->getInternationalName(), "language" => $language]);
		
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
		
		$mbArray = new \Doctrine\Common\Collections\ArrayCollection();
		
		foreach($entityToCopy->getMovieBiographies() as $mbToCopy) {
			$mb = new MovieBiography();
			
			$biography = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $mbToCopy->getBiography()->getInternationalName(), "language" => $language]);
			
			if(empty($biography))
				continue;
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
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());
			
			$entity->setIllustration($illustration);
		}

		$request->setLocale($language->getAbbreviation());

		$twig = 'movie/MovieAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }
	
	public function wikidataAction(Request $request, \App\Service\Wikidata $wikidata)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getMovieDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
}