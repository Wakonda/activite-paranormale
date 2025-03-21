<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\News;
use App\Entity\NewsTags;
use App\Entity\Language;
use App\Entity\Licence;
use App\Entity\State;
use App\Entity\Theme;
use App\Entity\FileManagement;
use App\Form\Type\NewsAdminType;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;
use App\Service\APImgSize;

/**
 * Actualite controller.
 *
 */
class NewsAdminController extends AdminGenericController
{
	protected $entityName = 'News';
	protected $className = News::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "News_Admin_Index"; 
	protected $showRoute = "News_Admin_Show";
	protected $formName = 'ap_news_newsadmintype';

	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new NewsTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'news/NewsAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'news/NewsAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = NewsAdminType::class;
		$entity = new News();

		$twig = 'news/NewsAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = NewsAdminType::class;
		$entity = new News();

		$twig = 'news/NewsAdmin/new.html.twig';

		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = NewsAdminType::class;

		$twig = 'news/NewsAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = NewsAdminType::class;

		$twig = 'news/NewsAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\NewsComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\NewsVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository(NewsTags::class)->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($em, $id);
    }

	/* FONCTION DE COMPTAGE */
	public function countNewsByStateAction(EntityManagerInterface $em, $state)
	{
		$countNewsByStateAdmin = $em->getRepository($this->className)->countNewsByStateAdmin($state);
		return new Response($countNewsByStateAdmin);
	}

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGenericAction($request, $em);
		$output = $informationArray['output'];

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";
			
			$row[] =  $entity->getId();
			$row[] =  $entity->getTitle();
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());
			$row[] =  $entity->getPseudoUsed();
			$row[] =  !empty($theme = $entity->getTheme()) ? $theme->getTitle() : "";
			
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => $entity->getState()->getInternationalName(), 'language' => $language]);
			$row[] =  $state->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('News_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('News_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>
			";
			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

    public function WYSIWYGUploadFileAction(Request $request, APImgSize $imgSize)
    {
		return $this->WYSIWYGUploadFileGenericAction($request, $imgSize, new News());
    }

	public function reloadThemeByLanguageAction(Request $request, EntityManagerInterface $em)
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
		
		$response = new Response(json_encode($translateArray));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('News_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}

	public function changeStateAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $id, $state)
	{
		$language = $request->getLocale();

		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);

		$entity = $em->getRepository(News::class)->find($id);
		
		$entity->setState($state);

		if($state->getInternationalName() == "Validate") {
			if(empty($entity->getTheme()))
				return $this->redirect($this->generateUrl('News_Admin_Edit', ['id' => $id]));
		}

		$em->persist($entity);
		$em->flush();

		if($state->getInternationalName() == "Validate")
			$this->addFlash('success', $translator->trans('news.admin.NewsPublished', [], 'validators'));
		else
			$this->addFlash('success', $translator->trans('news.admin.NewsRefused', [], 'validators'));
		
		return $this->redirect($this->generateUrl('News_Admin_Show', ['id' => $id]));
	}
	
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
	}

	public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
	{
		$formType = NewsAdminType::class;
		$entity = new News();

		$entityToCopy = $em->getRepository(News::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$theme = $em->getRepository(Theme::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getTheme()->getInternationalName()]);
		$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getState()->getInternationalName()]);
		
		$currentLanguagesWebsite = explode(",", $_ENV["LANGUAGES"]);
		if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite)) {
			$languageEnglish = $em->getRepository(Language::class)->findOneBy(['abbreviation' => 'en']);
			$state = $em->getRepository(State::class)->findOneBy(["language" => $languageEnglish, "internationalName" => $entityToCopy->getState()->getInternationalName()]);
		}

		if(empty($state)) {
			$defaultLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
			$state = $em->getRepository(State::class)->findOneBy(["language" => $defaultLanguage, "internationalName" => "Validate"]);
		}

		$entity->setState($state);
		$entity->setSource($entityToCopy->getSource());

		if(!empty($theme))
			$entity->setTheme($theme);

		$entity->setLanguage($language);

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

		$twig = 'news/NewsAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}

	public function deleteMultiple(Request $request, EntityManagerInterface $em)
	{
		$ids = json_decode($request->request->get("ids"));

		$entities = $em->getRepository($this->className)->findBy(['id' => $ids]);

		foreach($entities as $entity)
			$em->remove($entity);

		$em->flush();

		return new Response();
	}
}