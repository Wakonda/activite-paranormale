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
		
		// return new JsonResponse(json_decode('{"title":"2012","url":"https:\/\/fr.wikipedia.org\/wiki\/2012_(film)","person":{"director":{"Q60100":"Roland Emmerich"},"screenwriter":{"Q60100":"Roland Emmerich","Q78652":"Harald Kloser"},"actor":{"Q106175":"John Cusack","Q317343":"Chiwetel Ejiofor","Q131332":"Amanda Peet","Q343510":"Oliver Platt","Q229029":"Thandiwe Newton","Q192165":"Danny Glover","Q201279":"Woody Harrelson","Q514527":"Tom McCarthy","Q554506":"Liam James","Q268076":"Morgan Lily","Q266881":"Beatrice Rosen","Q927102":"Johann Urb","Q449900":"George Segal","Q453724":"John Billingsley","Q724227":"Jimi Mistry","Q938855":"Chin Han","Q117619":"Osric Chau","Q308792":"Patrick Bauchau","Q3453826":"Ryan McDonald","Q1321265":"Stephen McHattie","Q207149":"Zlatko Buri\u0107","Q6370861":"Karin Konoval","Q163234":"Blu Mankuma","Q535886":"Merrilyn Gann"},"executiveProducer":{"Q60100":"Roland Emmerich"},"directorOfPhotography":{"Q648611":"Dean Semler"},"filmEditor":{"Q1173825":"David Brenner"},"costumDesigner":{"Q95680682":"Shay Cunliffe"},"composer":{"Q78652":"Harald Kloser","Q450156":"Thomas Wanker"},"producer":{"Q78652":"Harald Kloser","Q60100":"Roland Emmerich","Q2417149":"Mark Gordon","Q521038":"Larry J. Franco"}},"publicationDate":[{"mainsnak":{"snaktype":"value","property":"P577","hash":"155bb10da6031b15f30d0a409765f18407917e60","datavalue":{"value":{"time":"+2009-11-13T00:00:00Z","timezone":0,"before":0,"after":0,"precision":11,"calendarmodel":"http:\/\/www.wikidata.org\/entity\/Q1985727"},"type":"time"},"datatype":"time"},"type":"statement","qualifiers":{"P291":[{"snaktype":"value","property":"P291","hash":"d030038c836f9ed0d37c69cb8c389d689ebeb05a","datavalue":{"value":{"entity-type":"item","numeric-id":30,"id":"Q30"},"type":"wikibase-entityid"},"datatype":"wikibase-item"}]},"qualifiers-order":["P291"],"id":"Q184605$EAAFCC4C-2BB7-4CCA-A306-FE74EE8A9ACB","rank":"normal","references":[{"hash":"92fa9c32b83f98b6683fa406e4adda21c4a64055","snaks":{"P854":[{"snaktype":"value","property":"P854","hash":"038cd77b96d793d41094d3377eaef3e3375b5e17","datavalue":{"value":"http:\/\/www.boxofficemojo.com\/movies\/?id=2012.htm","type":"string"},"datatype":"url"}]},"snaks-order":["P854"]}]},{"mainsnak":{"snaktype":"value","property":"P577","hash":"155bb10da6031b15f30d0a409765f18407917e60","datavalue":{"value":{"time":"+2009-11-13T00:00:00Z","timezone":0,"before":0,"after":0,"precision":11,"calendarmodel":"http:\/\/www.wikidata.org\/entity\/Q1985727"},"type":"time"},"datatype":"time"},"type":"statement","qualifiers":{"P291":[{"snaktype":"value","property":"P291","hash":"7e3c5acd8ed74c218a2b55f9a0f77447daa0253b","datavalue":{"value":{"entity-type":"item","numeric-id":34,"id":"Q34"},"type":"wikibase-entityid"},"datatype":"wikibase-item"}]},"qualifiers-order":["P291"],"id":"Q184605$aed53b08-4cd5-ab71-3c34-039038d2de90","rank":"normal","references":[{"hash":"32fe79abd501845fddc7ef0fe11f7dad36b9f0ba","snaks":{"P854":[{"snaktype":"value","property":"P854","hash":"cf4273a9068d3d58e455f809d7878108647beb69","datavalue":{"value":"http:\/\/www.sfi.se\/sv\/svensk-filmdatabas\/Item\/?itemid=68917&type=MOVIE&iv=Basic","type":"string"},"datatype":"url"}]},"snaks-order":["P854"]}]},{"mainsnak":{"snaktype":"value","property":"P577","hash":"6efa3211c3e45f3db3838b591d0e41d6c651470b","datavalue":{"value":{"time":"+2009-11-12T00:00:00Z","timezone":0,"before":0,"after":0,"precision":11,"calendarmodel":"http:\/\/www.wikidata.org\/entity\/Q1985727"},"type":"time"},"datatype":"time"},"type":"statement","qualifiers":{"P291":[{"snaktype":"value","property":"P291","hash":"a15ed95482d52eea03d59e098dae9e31dafef9bd","datavalue":{"value":{"entity-type":"item","numeric-id":183,"id":"Q183"},"type":"wikibase-entityid"},"datatype":"wikibase-item"},{"snaktype":"value","property":"P291","hash":"8dd6692b0f05cf604df00898f8bd1efd33aa4432","datavalue":{"value":{"entity-type":"item","numeric-id":28,"id":"Q28"},"type":"wikibase-entityid"},"datatype":"wikibase-item"}]},"qualifiers-order":["P291"],"id":"Q184605$B0821368-FDDB-4D24-AB72-766495ECE8C7","rank":"normal","references":[{"hash":"ae78b0ab699c2e9a01e0291263035911d2ab2ef7","snaks":{"P248":[{"snaktype":"value","property":"P248","hash":"6ff43c6ce02fc9e2012771b5595093e7f0c33162","datavalue":{"value":{"entity-type":"item","numeric-id":37312,"id":"Q37312"},"type":"wikibase-entityid"},"datatype":"wikibase-item"}],"P854":[{"snaktype":"value","property":"P854","hash":"a33246784bdf35d793edf5dbfdcda655845b080c","datavalue":{"value":"http:\/\/www.imdb.com\/title\/tt1190080\/releaseinfo","type":"string"},"datatype":"url"}],"P813":[{"snaktype":"value","property":"P813","hash":"3b484e449d058ead231f4a9bd74b48ce76b48bbc","datavalue":{"value":{"time":"+2017-04-14T00:00:00Z","timezone":0,"before":0,"after":0,"precision":11,"calendarmodel":"http:\/\/www.wikidata.org\/entity\/Q1985727"},"type":"time"},"datatype":"time"}],"P407":[{"snaktype":"value","property":"P407","hash":"daf1c4fcb58181b02dff9cc89deb084004ddae4b","datavalue":{"value":{"entity-type":"item","numeric-id":1860,"id":"Q1860"},"type":"wikibase-entityid"},"datatype":"wikibase-item"}]},"snaks-order":["P248","P854","P813","P407"]},{"hash":"cf78a1bcee63cc95f54f1a3e090879ab8b3b7d5f","snaks":{"P854":[{"snaktype":"value","property":"P854","hash":"b20d181be4d408820e022956a482312bb1797b7f","datavalue":{"value":"http:\/\/nmhh.hu\/dokumentum\/158984\/2009_filmbemutatok_osszes.xls","type":"string"},"datatype":"url"}]},"snaks-order":["P854"]},{"hash":"3910a55cde36f72fbfc020500cd1fd0f85d6f3b5","snaks":{"P248":[{"snaktype":"value","property":"P248","hash":"8d47e4ff1432a81ed9918a0b2603e6b1d1af79ce","datavalue":{"value":{"entity-type":"item","numeric-id":15706812,"id":"Q15706812"},"type":"wikibase-entityid"},"datatype":"wikibase-item"}]},"snaks-order":["P248"]}]},{"mainsnak":{"snaktype":"value","property":"P577","hash":"5287438b6734cde173ca0c51689565251073dbd6","datavalue":{"value":{"time":"+2009-11-11T00:00:00Z","timezone":0,"before":0,"after":0,"precision":11,"calendarmodel":"http:\/\/www.wikidata.org\/entity\/Q1985727"},"type":"time"},"datatype":"time"},"type":"statement","qualifiers":{"P291":[{"snaktype":"value","property":"P291","hash":"0e14b091e4290b83034c405fdea4955fecb7f935","datavalue":{"value":{"entity-type":"item","numeric-id":142,"id":"Q142"},"type":"wikibase-entityid"},"datatype":"wikibase-item"}]},"qualifiers-order":["P291"],"id":"Q184605$148ab7e6-4a3e-6830-9952-634d0f2b3dad","rank":"normal"}],"duration":{"amount":158,"unit":"minute"},"origin":{"country":{"alpha2":"US","alpga3":"USA"}},"reviewScores":[{"score":"39%","source":"Rotten Tomatoes"},{"score":"5.2\/10","source":"Rotten Tomatoes"}],"boxOffice":{"amount":791657398,"unit":"United States dollar"},"cost":{"amount":"+280000000","unit":"United States dollar"},"websites":{"website":{"http:\/\/www.sonypictures.com\/movies\/2012\/":"http:\/\/www.sonypictures.com\/movies\/2012\/","http:\/\/www.whowillsurvive2012.com":"http:\/\/www.whowillsurvive2012.com"}}}'));
		
		// dd(json_encode($res));
		// $res["country"]["id"] = (!empty($c = $em->getRepository(Country::class)->findOneBy(["language" => $language, "internationalName" => $res["origin"]["country"]["alpha2"]]))) ? $c->getId() : null;
// dd($res);
		return new JsonResponse($res);
	}
}