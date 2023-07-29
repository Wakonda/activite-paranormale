<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Video;
use App\Entity\VideoTags;
use App\Entity\Language;
use App\Entity\Theme;
use App\Entity\State;
use App\Entity\Licence;
use App\Form\Type\VideoAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

/**
 * Video controller.
 *
 */
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

		$params = $request->request->get($form->getName());

		if(isset($params['mediaVideo_selector']) and !empty($params['mediaVideo_selector']))
			$entityBindded->setMediaVideo($params['mediaVideo_selector']);

		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new VideoTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'video/VideoAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'video/VideoAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = VideoAdminType::class;
		$entity = new Video();

		$twig = 'video/VideoAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ["locale" => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = VideoAdminType::class;
		$entity = new Video();

		$twig = 'video/VideoAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ["locale" => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = VideoAdminType::class;

		$twig = 'video/VideoAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ["locale" => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = VideoAdminType::class;
		$twig = 'video/VideoAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ["locale" => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository("\App\Entity\VideoComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\VideoVote")->findBy(["video" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\VideoTags")->findBy(["entity" => $id]);
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
			$row[] = $entity->getTheme()->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('Video_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('Video_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadListsByLanguageAction(Request $request, EntityManagerInterface $em)
	{
		$em = $this->getDoctrine()->getManager();

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

	public function chooseExistingFileAction()
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

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('Video_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}

	public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
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
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}
}