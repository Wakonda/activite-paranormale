<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Movies\EpisodeTelevisionSerie;
use App\Entity\EpisodeTelevisionSerieTags;
use App\Entity\Movies\TelevisionSerie;
use App\Entity\TelevisionSerieTags;
use App\Entity\Biography;
use App\Entity\Movies\TelevisionSerieBiography;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\Region;
use App\Entity\Language;
use App\Entity\Theme;
use App\Form\Type\EpisodeTelevisionSerieAdminType;
use App\Service\ConstraintControllerValidator;
use Doctrine\Common\Collections\ArrayCollection;
use App\Service\TagsManagingGeneric;

#[Route('/admin/episodetelevisionserie')]
class EpisodeTelevisionSerieAdminController extends AdminGenericController
{
	protected $entityName = 'EpisodeTelevisionSerie';
	protected $className = EpisodeTelevisionSerie::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "EpisodeTelevisionSerie_Admin_Index"; 
	protected $showRoute = "EpisodeTelevisionSerie_Admin_Show";
	protected $formName = 'ap_movie_televisionserieadmintype';

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		foreach ($form->get('episodeTelevisionSerieBiographies') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($em, $entityBindded, $form, "episodeTelevisionSerieBiographies");
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		$originalTelevisionSerieBiographies = new ArrayCollection($em->getRepository(TelevisionSerieBiography::class)->findBy(["televisionSerie" => $entityBindded->getTelevisionSerie()->getId(), "episodeTelevisionSerie" => $entityBindded->getId()]));
		
		foreach($originalTelevisionSerieBiographies as $originalTelevisionSerieBiography)
		{
			if(false === $entityBindded->getEpisodeTelevisionSerieBiographies()->contains($originalTelevisionSerieBiography))
			{
				$em->remove($originalTelevisionSerieBiography);
			}
		}

		foreach($entityBindded->getEpisodeTelevisionSerieBiographies() as $mb)
		{
			if(!empty($mb->getBiography())) {
				$mb->setEpisodeTelevisionSerie($entityBindded);
				$em->persist($mb);
			}
		}

		$em->flush();
		
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new EpisodeTelevisionSerieTags(), $entityBindded);
	}

	#[Route('/{televisionSerieId}', name: 'EpisodeTelevisionSerie_Admin_Index', requirements: ['televisionSerieId' => '\d+'])]
    public function index(Int $televisionSerieId)
    {
		$twig = 'movie/EpisodeTelevisionSerieAdmin/index.html.twig';
		return $this->render($twig, ["televisionSerieId" => $televisionSerieId]);
    }

	#[Route('/{id}/show', name: 'EpisodeTelevisionSerie_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'movie/EpisodeTelevisionSerieAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new/{televisionSerieId}', name: 'EpisodeTelevisionSerie_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em, Int $televisionSerieId)
    {
		$formType = EpisodeTelevisionSerieAdminType::class;
		$entity = new EpisodeTelevisionSerie();
		
		$entity->setTelevisionSerie($em->getRepository(TelevisionSerie::class)->find($televisionSerieId));

		$twig = 'movie/EpisodeTelevisionSerieAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType);
    }

	#[Route('/create/{televisionSerieId}', name: 'EpisodeTelevisionSerie_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, Int $televisionSerieId)
    {
		$formType = EpisodeTelevisionSerieAdminType::class;
		$entity = new EpisodeTelevisionSerie();

		$entity->setTelevisionSerie($em->getRepository(TelevisionSerie::class)->find($televisionSerieId));

		$twig = 'movie/EpisodeTelevisionSerieAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }

	#[Route('/{id}/edit', name: 'EpisodeTelevisionSerie_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(EpisodeTelevisionSerie::class)->find($id);
		$formType = EpisodeTelevisionSerieAdminType::class;

		$twig = 'movie/EpisodeTelevisionSerieAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
    }

	#[Route('/{id}/update', name: 'EpisodeTelevisionSerie_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = EpisodeTelevisionSerieAdminType::class;
		$twig = 'movie/EpisodeTelevisionSerieAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType);
    }

	#[Route('/{id}/delete', name: 'EpisodeTelevisionSerie_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$tags = $em->getRepository("\App\Entity\EpisodeTelevisionSerieTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'EpisodeTelevisionSerie_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, Int $televisionSerieId)
	{
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

		// Search on individual column
		$searchByColumns = [];
		$iColumns = $request->query->get('iColumns');

		for($i=0; $i < $iColumns; $i++)
		{
			$searchByColumns[] = $request->query->get('sSearch_'.$i);
		}

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $televisionSerieId);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $televisionSerieId, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getTitle();
			$row[] = $entity->getSeason();
			$row[] = $entity->getEpisodeNumber();
			$row[] = "
			 <a href='".$this->generateUrl('EpisodeTelevisionSerie_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('EpisodeTelevisionSerie_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/reload_theme_by_language', name: 'EpisodeTelevisionSerie_Admin_ReloadThemeByLanguage')]
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

	#[Route('/wikidata', name: 'EpisodeTelevisionSerie_Admin_Wikidata')]
	public function wikidata(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getEpisodeTelevisionSerieDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
}