<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Music;
use App\Entity\Album;
use App\Entity\Language;
use App\Entity\MusicBiography;
use App\Form\Type\MusicAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * musicGestion controller.
 *
 */
class MusicAdminController extends AdminGenericController
{
	protected $entityName = 'Music';
	protected $className = Music::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Music_Admin_Index"; 
	protected $showRoute = "Music_Admin_Show";
	protected $illustrations = [["field" => "musicPieceFile"]];
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$data = $form->all();

		if(isset($data['music_selector']) and $entityBindded->getMusicPieceFile() == "")
			$entityBindded->setMusicPieceFile($data['music_selector']->getNormData());
	
		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		
		if(!empty($entityBindded->getAlbum())) {
			$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

			if($searchForDoublons > 0)
				$form->get('musicPiece')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
		}
		if(empty($entityBindded->getMusicPieceFile()) and empty($entityBindded->getEmbeddedCode())) {
			$form->get('musicPieceFile')->addError(new FormError($translator->trans('admin.error.NotBlankVideoOrAudio', [], 'validators')));
			$form->get('embeddedCode')->addError(new FormError($translator->trans('admin.error.NotBlankVideoOrAudio', [], 'validators')));
		}
		foreach ($form->get('musicBiographies') as $formChild)
			if(empty($formChild->get('internationalName')->getData()))
				$formChild->get('biography')->addError(new FormError($translator->trans('biography.admin.YouMustValidateThisBiography', [], 'validators')));

		if($form->isValid())
			$this->saveNewBiographies($entityBindded, $form, "musicBiographies");
	}

	public function postValidationAction($form, $entityBindded)
	{
		$em = $this->getDoctrine()->getManager();
		$originalMusics = new ArrayCollection($em->getRepository(MusicBiography::class)->findBy(["music" => $entityBindded->getId()]));
		
		foreach($originalMusics as $originalMusic)
		{
			if(false === $entityBindded->getMusicBiographies()->contains($originalMusic))
			{
				$em->remove($originalMusic);
			}
		}

		foreach($entityBindded->getMusicBiographies() as $mb)
		{
			if(!empty($mb->getBiography())) {
				$mb->setMusic($entityBindded);
				$em->persist($mb);
			}
		}

		$em->flush();
	}

    public function indexAction()
    {
		$twig = 'music/MusicAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'music/MusicAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = MusicAdminType::class;
		$entity = new Music();
		
		if ($request->query->has("albumId")) {
			$entity->setAlbum($this->getDoctrine()->getManager()->getRepository(Album::class)->find($request->query->get("albumId")));
		}
		
		$language = $this->getDoctrine()->getManager()->getRepository(Language::class)->findOneBy(array("abbreviation" => $request->getLocale()));

		$twig = 'music/MusicAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ["language" => $language]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = MusicAdminType::class;
		$entity = new Music();
		
		$twig = 'music/MusicAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction($id)
    {
		$formType = MusicAdminType::class;

		$twig = 'music/MusicAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = MusicAdminType::class;
		$twig = 'music/MusicAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType);
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
			$row[] = $entity->getAlbum()->getArtist()->getTitle();
			$row[] = $entity->getMusicPiece();
			$row[] = "
			 <a href='".$this->generateUrl('Music_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Music_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function chooseExistingFileAction()
    {
		$webPath = $this->getParameter('kernel.project_dir').'/public/extended/flash/Music/MP3/';
	
		$finder = new Finder();
		$finder->files()->in($webPath);
		$filesArray = [];
		
		foreach ($finder as $file)
		{
			$filesArray[] = $file->getRelativePathname();
		}
	
		return $this->render('music/MusicAdmin/chooseExistingFile.html.twig', array(
			"filesArray" => $filesArray
		));	
    }

    public function indexByAlbumAction(Int $albumId)
    {
		$twig = 'music/MusicAdmin/indexByAlbum.html.twig';
		return $this->render($twig, ["albumId" => $albumId]);
    }

	public function indexByAlbumDatatablesAction(Request $request, TranslatorInterface $translator, Int $albumId)
	{
		$em = $this->getDoctrine()->getManager();
		
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexByAlbumAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $albumId);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexByAlbumAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $albumId, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getMusicPiece();
			$row[] = "
			 <a href='".$this->generateUrl('Music_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Music_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function wikidataAction(Request $request, \App\Service\Wikidata $wikidata)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getMusicDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}
}