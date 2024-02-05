<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Movies\TelevisionSerie;
use App\Entity\Movies\EpisodeTelevisionSerie;
use App\Entity\TelevisionSerieTags;
use App\Entity\Biography;
use App\Entity\Movies\TelevisionSerieBiography;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Region;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\FileManagement;
use App\Form\Type\TelevisionSerieAdminType;
use App\Service\ConstraintControllerValidator;
use Doctrine\Common\Collections\ArrayCollection;
use App\Service\TagsManagingGeneric;

/**
 * TelevisionSerieAdmin controller.
 *
 */
class TelevisionSerieAdminController extends AdminGenericController
{
	protected $entityName = 'TelevisionSerie';
	protected $className = TelevisionSerie::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "TelevisionSerie_Admin_Index"; 
	protected $showRoute = "TelevisionSerie_Admin_Show";
	protected $formName = 'ap_movie_televisionserieadmintype';
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		foreach ($form->get('televisionSerieBiographies') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($em, $entityBindded, $form, "televisionSerieBiographies");
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		$originalTelevisionSerieBiographies = new ArrayCollection($em->getRepository(TelevisionSerieBiography::class)->findBy(["televisionSerie" => $entityBindded->getId(), "episodeTelevisionSerie" => null]));

		foreach($originalTelevisionSerieBiographies as $originalTelevisionSerieBiography)
		{
			if(false === $entityBindded->getTelevisionSerieBiographies()->contains($originalTelevisionSerieBiography))
			{
				$em->remove($originalTelevisionSerieBiography);
			}
		}

		foreach($entityBindded->getTelevisionSerieBiographies() as $mb)
		{
			if(!empty($mb->getBiography())) {
				$mb->setTelevisionSerie($entityBindded);
				$em->persist($mb);
			}
		}

		$em->flush();

		if($form->isValid()) {
			$datas = !empty($d = json_decode($form->get("episode")->getData(), true)) ? $d : [];

			foreach($datas as $season => $values) {
				foreach($values as $episodeNumber => $value) {
					$episode = new EpisodeTelevisionSerie();
					$episode->setWikidata($value["wikidata"]);
					$episode->setTitle($value["title"]);
					
					if(isset($value["identifiers"]) and !empty($value["identifiers"]))
						$episode->setIdentifiers(json_encode($value["identifiers"]));
					
					if(isset($value["duration"]) and !empty($value["duration"])) {
						if($value["duration"]["unit"] == "minute") {
							$episode->setDuration($value["duration"]["amount"]);
						}
					}

					if(!empty($value["date"]))
						$episode->setReleaseDate(new \DateTime(implode("-", $value["date"])));
					$episode->setSeason($season);
					
					$en = $episodeNumber + 1;
					$episode->setEpisodeNumber($en);
					$episode->setTelevisionSerie($entityBindded);
				
					$searchForDoublons = $em->getRepository(EpisodeTelevisionSerie::class)->countForDoublons($episode);
				
					if($searchForDoublons == 0)
						$em->persist($episode);
				}
			}

			$em->flush();
		}

		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new TelevisionSerieTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'movie/TelevisionSerieAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'movie/TelevisionSerieAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = TelevisionSerieAdminType::class;
		$entity = new TelevisionSerie();

		$twig = 'movie/TelevisionSerieAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = TelevisionSerieAdminType::class;
		$entity = new TelevisionSerie();

		$twig = 'movie/TelevisionSerieAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(TelevisionSerie::class)->find($id);
		$formType = TelevisionSerieAdminType::class;

		$twig = 'movie/TelevisionSerieAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = TelevisionSerieAdminType::class;
		$twig = 'movie/TelevisionSerieAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\TelevisionSerieComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\TelevisionSerieVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\TelevisionSerieTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($em, $id);
    }
	
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
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
			 <a href='".$this->generateUrl('TelevisionSerie_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('TelevisionSerie_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('TelevisionSerie_Admin_LoadImageSelectorColorbox');
	}

	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}

	public function reloadThemeByLanguageAction(Request $request, EntityManagerInterface $em)
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
	
	public function autocompleteAction(Request $request, EntityManagerInterface $em)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		
		if(is_numeric($locale)) {
			$language = $em->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}
		
		$datas =  $em->getRepository(TelevisionSerie::class)->getAutocomplete($locale, $query);
		
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
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = TelevisionSerieAdminType::class;
		$entity = new TelevisionSerie();

		$entityToCopy = $em->getRepository(TelevisionSerie::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$country = null;
		
		if(!empty($entityToCopy->getCountry()))
			$country = $em->getRepository(Region::class)->findOneBy(["internationalName" => $entityToCopy->getCountry()->getInternationalName(), "language" => $language]);
		
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
		
		$mbArray = new \Doctrine\Common\Collections\ArrayCollection();
		
		foreach($entityToCopy->getTelevisionSerieBiographies() as $mbToCopy) {
			$mb = new TelevisionSerieBiography();
			
			$biography = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $mbToCopy->getBiography()->getInternationalName(), "language" => $language]);
			
			if(empty($biography))
				continue;
			$mb->setRole($mbToCopy->getRole());
			$mb->setOccupation($mbToCopy->getOccupation());
			$mb->setTelevisionSerie($entity);
			$mb->setBiography($biography);
			
			$mbArray->add($mb);
		}
		
		$entity->setTelevisionSerieBiographies($mbArray);

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

		$request->setLocale($language->getAbbreviation());

		$twig = 'movie/TelevisionSerieAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }
	
	public function wikidataAction(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getTelevisionSerieDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
}