<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Video;
use App\Entity\VideoTags;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\State;
use App\Entity\Licence;
use App\Form\Type\VideoAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

#[Route('/admin/video')]
class VideoAdminController extends AdminGenericController
{
	protected $entityName = 'Video';
	protected $className = Video::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Video_Admin_Index"; 
	protected $showRoute = "Video_Admin_Show";
	protected $formName = "ap_video_videoadmintype";
	
	protected $illustrations = [["field" => "photo", 'selectorFile' => 'photo_selector']];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal, $filesArray = null)
	{
		if($entityBindded->getPlatform() != "AP")
			unset($this->illustrations[1]);

		$params = $request->request->all($form->getName());

		if(isset($params['mediaVideo_selector']) and !empty($params['mediaVideo_selector']))
			$entityBindded->setMediaVideo($params['mediaVideo_selector']);

		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new VideoTags(), $entityBindded);
	}

	#[Route('/', name: 'Video_Admin_Index')]
    public function index()
    {
		$twig = 'video/VideoAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Video_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'video/VideoAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'Video_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = VideoAdminType::class;
		$entity = new Video();

		$twig = 'video/VideoAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ["locale" => $request->getLocale()]);
    }

	#[Route('/create', name: 'Video_Admin_Create', requirements: ['_method' => "post"])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = VideoAdminType::class;
		$entity = new Video();

		$twig = 'video/VideoAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ["locale" => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'Video_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = VideoAdminType::class;

		$twig = 'video/VideoAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ["locale" => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'Video_Admin_Update', requirements: ['_method' => "post"])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = VideoAdminType::class;
		$twig = 'video/VideoAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ["locale" => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'Video_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\VideoComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\VideoVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\VideoTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }

	#[Route('/archive/{id}', name: 'Video_Admin_Archive', requirements: ['id' => "\d+"])]
	public function archive(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
	}

	#[Route('/datatables', name: 'Video_Admin_IndexDatatables', requirements: ['_method' => "get"])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $entity->getPlatform();
			
			if($entity->getAvailable())
				$state = '<span class="text-success"><i class="fas fa-check" aria-hidden="true"></i></span>';
			else
				$state = '<span class="text-danger"><i class="fas fa-times" aria-hidden="true"></i></span>';
			$row[] = $state;
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = !empty($t = $entity->getTheme()) ? $t->getTitle() : null;
			
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => $entity->getState()->getInternationalName(), 'language' => $language]);
			$row[] =  $state->getTitle();
			
			$row[] = "
			 <a href='".$this->generateUrl('Video_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('Video_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/reloadlistsbylanguage', name: 'Video_Admin_ReloadListsByLanguage')]
	public function reloadListsByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];

		if(!empty($language))
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList($language->getAbbreviation(), $request->getLocale());

			$currentLanguagesWebsite = explode(",", $_ENV["LANGUAGES"]);
			if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite))
				$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => 'en']);

			$states = $em->getRepository(State::class)->findByLanguage($language, ['title' => 'ASC']);
			$licences = $em->getRepository(Licence::class)->findByLanguage($language, ['title' => 'ASC']);
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList(null, $request->getLocale());
			$states = $em->getRepository(State::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
		}

		$themeArray = [];
		$stateArray = [];
		$licenceArray = [];
		
		foreach($themes as $theme)
			$themeArray[] = ["id" => $theme["id"], "title" => $theme["title"]];

		$translateArray['theme'] = $themeArray;

		foreach($states as $state)
			$stateArray[] = ["id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName()];

		$translateArray['state'] = $stateArray;

		foreach($licences as $licence)
			$licenceArray[] = ["id" => $licence->getId(), "title" => $licence->getTitle()];

		$translateArray['licence'] = $licenceArray;

		return new JsonResponse($translateArray);
	}

	#[Route('/video_duration', name: 'Video_Admin_VideoDuration')]
	public function getVideoDuration(Request $request) {
		$videoService = new \App\Service\Video(base64_decode($request->query->get("embeddedCode")));
		return new JsonResponse($videoService->getDuration());
	}

	#[Route('/chooseexistingfile', name: 'Video_Admin_ChooseExistingFile')]
	public function chooseExistingFile()
    {
		$webPath = $this->getParameter('kernel.project_dir').'/public/extended/flash/Video/KAWAplayer_v1/videos/';
	
		$finder = new Finder();
		$finder->files()->in($webPath);
		$filesArray = [];
		
		foreach ($finder as $file)
			$filesArray[] = $file->getRelativePathname();
	
		return $this->render('video/VideoAdmin/chooseExistingFile.html.twig', [
			"filesArray" => $filesArray
		]);
    }

	#[Route('/showImageSelectorColorbox', name: 'Video_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('Video_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'Video_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	#[Route('/internationalization/{id}', name: 'Video_Admin_Internationalization')]
	public function internationalization(Request $request, EntityManagerInterface $em, $id)
	{
		$formType = VideoAdminType::class;
		$entity = new Video();

		$entityToCopy = $em->getRepository($this->className)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$theme = $em->getRepository(Theme::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getTheme()->getInternationalName()]);
		$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getState()->getInternationalName()]);

		if(empty($state)) {
			$defaultLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
			$state = $em->getRepository(State::class)->findOneBy(["language" => $defaultLanguage, "internationalName" => "Validate"]);
		}

		$entity->setState($state);

		$entity->setPlatform($entityToCopy->getPlatform());
		$entity->setMediaVideo($entityToCopy->getMediaVideo());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setEmbeddedCode($entityToCopy->getEmbeddedCode());
		$entity->setDuration($entityToCopy->getDuration());
		
		if(!empty($theme))
			$entity->setTheme($theme);

		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'video/VideoAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}

	/* FONCTION DE COMPTAGE */
	public function countByState(EntityManagerInterface $em, $state)
	{
		$countByStateAdmin = $em->getRepository($this->className)->countByStateAdmin($state);
		return new Response($countByStateAdmin);
	}

	#[Route('/delete_multiple', name: 'Video_Admin_DeleteMultiple')]
	public function deleteMultiple(Request $request, EntityManagerInterface $em)
	{
		$ids = json_decode($request->request->get("ids"));

		$entities = $em->getRepository($this->className)->findBy(['id' => $ids]);

		foreach($entities as $entity)
			$em->remove($entity);

		$em->flush();

		return new Response();
	}

	#[Route('/change_state/{id}/{state}', name: 'Video_Admin_ChangeState', requirements: ['id' => "\d+"])]
	public function changeState(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $id, $state)
	{
		$language = $request->getLocale();

		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);

		$entity = $em->getRepository(Video::class)->find($id);
		
		$entity->setState($state);

		if($state->getInternationalName() == "Validate") {
			if(empty($entity->getTheme()))
				return $this->redirect($this->generateUrl('Video_Admin_Edit', ['id' => $id]));
		}

		$em->persist($entity);
		$em->flush();

		if($state->getInternationalName() == "Validate")
			$this->addFlash('success', $translator->trans('news.admin.NewsPublished', [], 'validators'));
		else
			$this->addFlash('success', $translator->trans('news.admin.NewsRefused', [], 'validators'));

		return $this->redirect($this->generateUrl('Video_Admin_Show', ['id' => $id]));
	}
}