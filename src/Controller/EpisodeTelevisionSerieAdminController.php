<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
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

/**
 * EpisodeTelevisionSerieAdminController controller.
 *
 */
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
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		foreach ($form->get('episodeTelevisionSerieBiographies') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($entityBindded, $form, "episodeTelevisionSerieBiographies");
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		$em = $this->getDoctrine()->getManager();
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

    public function indexAction(Int $televisionSerieId)
    {
		$twig = 'movie/EpisodeTelevisionSerieAdmin/index.html.twig';
		return $this->render($twig, ["televisionSerieId" => $televisionSerieId]);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'movie/EpisodeTelevisionSerieAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, Int $televisionSerieId)
    {
		$em = $this->getDoctrine()->getManager();
		$formType = EpisodeTelevisionSerieAdminType::class;
		$entity = new EpisodeTelevisionSerie();
		
		$entity->setTelevisionSerie($em->getRepository(TelevisionSerie::class)->find($televisionSerieId));

		$twig = 'movie/EpisodeTelevisionSerieAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, Int $televisionSerieId)
    {
		$formType = EpisodeTelevisionSerieAdminType::class;
		$entity = new EpisodeTelevisionSerie();

		$entity->setTelevisionSerie($em->getRepository(TelevisionSerie::class)->find($televisionSerieId));

		$twig = 'movie/EpisodeTelevisionSerieAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository(EpisodeTelevisionSerie::class)->find($id);
		$formType = EpisodeTelevisionSerieAdminType::class;

		$twig = 'movie/EpisodeTelevisionSerieAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = EpisodeTelevisionSerieAdminType::class;
		$twig = 'movie/EpisodeTelevisionSerieAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$em = $this->getDoctrine()->getManager();
		$tags = $em->getRepository("\App\Entity\EpisodeTelevisionSerieTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($em, $id);
    }

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, Int $televisionSerieId)
	{
		$em = $this->getDoctrine()->getManager();

		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
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

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

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

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadThemeByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
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
	
	public function wikidataAction(Request $request, \App\Service\Wikidata $wikidata)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getEpisodeTelevisionSerieDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
}