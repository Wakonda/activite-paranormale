<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Artist;
use App\Entity\ArtistBiography;
use App\Entity\Biography;
use App\Entity\FileManagement;
use App\Entity\Language;
use App\Entity\Region;
use App\Entity\MusicGenre;
use App\Form\Type\ArtistAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Artist controller.
 *
 */
class ArtistAdminController extends AdminGenericController
{
	protected $entityName = 'Artist';
	protected $className = Artist::class;

	protected $countEntities = "countAdmin"; 
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "Artist_Admin_Index"; 
	protected $showRoute = "Artist_Admin_Show";
	protected $formName = 'ap_music_artistadmintype';
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository(Artist::class)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		foreach ($form->get('artistBiographies') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));
		
		if($form->isValid())
			$this->saveNewBiographies($entityBindded, $form, "artistBiographies");
	}

	public function postValidationAction($form, $entityBindded)
	{
		$em = $this->getDoctrine()->getManager();
		$originalBiographies = new ArrayCollection($em->getRepository(ArtistBiography::class)->findBy(["artist" => $entityBindded->getId()]));
		
		foreach($originalBiographies as $originalBiography)
		{
			if(false === $entityBindded->getArtistBiographies()->contains($originalBiography))
			{
				$em->remove($originalBiography);
			}
		}

		foreach($entityBindded->getArtistBiographies() as $mb)
		{
			if(!empty($mb->getBiography())) {
				$mb->setArtist($entityBindded);
				$em->persist($mb);
			}
		}

		$em->flush();
	}

    public function indexAction()
    {
		$twig = 'music/ArtistAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }

    public function showAction($id)
    {
		$twig = 'music/ArtistAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = ArtistAdminType::class;
		$entity = new Artist();

		$twig = 'music/ArtistAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = ArtistAdminType::class;
		$entity = new Artist();

		$twig = 'music/ArtistAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }

    public function editAction($id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository(Artist::class)->find($id);
		$formType = ArtistAdminType::class;

		$twig = 'music/ArtistAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = ArtistAdminType::class;
		$twig = 'music/ArtistAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }

    public function deleteAction($id)
    {
		return $this->deleteGenericAction($id);
    }

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = (!empty($genre = $entity->getGenre())) ? $genre->getTitle() : "";
			$row[] = '<a href="'.$entity->getWebsite().'">'.$entity->getWebsite().'</a>';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			<a href='".$this->generateUrl('Artist_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			<a href='".$this->generateUrl('Artist_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function autocompleteAction(Request $request)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		
		if(is_numeric($locale)) {
			$language = $this->getDoctrine()->getManager()->getRepository(Language::class)->find($locale);
			$locale = (!empty($language)) ? $language->getAbbreviation() : null;
		}
		
		$datas =  $this->getDoctrine()->getManager()->getRepository(Artist::class)->getAutocomplete($locale, $query);
		
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

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Artist_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = ArtistAdminType::class;
		$entity = new Artist();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(Artist::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setGenre($entityToCopy->getGenre());
		$entity->setWebsite($entityToCopy->getWebsite());
		$entity->setWikidata($entityToCopy->getWikidata());
		
		$country = null;
		
		if(!empty($entityToCopy->getCountry()))
			$country = $em->getRepository(Region::class)->findOneBy(["internationalName" => $entityToCopy->getCountry()->getInternationalName(), "language" => $language]);
		
		$entity->setCountry($country);
		
		$mbArray = new \Doctrine\Common\Collections\ArrayCollection();
		
		foreach($entityToCopy->getArtistBiographies() as $mbToCopy) {
			$mb = new ArtistBiography();
			
			$biography = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $mbToCopy->getBiography()->getInternationalName(), "language" => $language]);
			
			if(empty($biography))
				continue;

			$mb->setOccupation($mbToCopy->getOccupation());
			$mb->setArtist($entity);
			$mb->setBiography($biography);
			
			$mbArray->add($mb);
		}
		
		$entity->setArtistBiographies($mbArray);

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

		$twig = 'music/ArtistAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	public function reloadByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];

		if(!empty($language))
			$countries = $em->getRepository(Region::class)->findByLanguage($language, ['title' => 'ASC']);
		else
			$countries = $em->getRepository(Region::class)->findAll();

		$countryArray = [];
		
		foreach($countries as $country)
			$countryArray[] = ["id" => $country->getId(), "title" => $country->getTitle()];

		$translateArray['country'] = $countryArray;

		if(!empty($language))
			$musicGenres = $em->getRepository(MusicGenre::class)->getAllGenresByLocale($language->getAbbreviation());
		else
			$musicGenres = $em->getRepository(MusicGenre::class)->findAll();

		$musicGenreArray = [];
		
		foreach($musicGenres as $musicGenre)
			$musicGenreArray[] = ["id" => $musicGenre->getId(), "title" => $musicGenre->getTitle()];

		$translateArray['musicGenre'] = $musicGenreArray;

		return new JsonResponse($translateArray);
	}

	public function wikidataAction(Request $request, \App\Service\Wikidata $wikidata)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");

		$res = $wikidata->getArtistDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
}