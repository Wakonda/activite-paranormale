<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
use App\Service\TwitterAPI;
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
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidationAction($form, $entityBindded)
	{
		(new TagsManagingGeneric($this->getDoctrine()->getManager()))->saveTags($form, $this->className, $this->entityName, new NewsTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'news/NewsAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'news/NewsAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = NewsAdminType::class;
		$entity = new News();

		$twig = 'news/NewsAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = NewsAdminType::class;
		$entity = new News();

		$twig = 'news/NewsAdmin/new.html.twig';

		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction($id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = NewsAdminType::class;

		$twig = 'news/NewsAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = NewsAdminType::class;
		
		$twig = 'news/NewsAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository("\App\Entity\NewsComment")->findBy(["news" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\NewsVote")->findBy(["news" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\NewsTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($id);
    }

	/* FONCTION DE COMPTAGE */
	public function countNewsByStateAction($state)
	{
		$em = $this->getDoctrine()->getManager();
		$countNewsByStateAdmin = $em->getRepository($this->className)->countNewsByStateAdmin($state);
		return new Response($countNewsByStateAdmin);
	}

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator, APDate $date)
	{
		$em = $this->getDoctrine()->getManager();
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));

		foreach($informationArray['entities'] as $entity)
		{
			$row = array();
			
			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";
			
			$row[] =  $entity->getId();
			$row[] =  $entity->getTitle();
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());
			$row[] =  $entity->getPseudoUsed();
			$row[] =  $entity->getTheme()->getTitle();
			
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => $entity->getState()->getInternationalName(), 'language' => $language));
			$row[] =  $state->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('News_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br>
			 <a href='".$this->generateUrl('News_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br>
			";
			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

    public function indexStateAction(Request $request, $state)
    {
        $em = $this->getDoctrine()->getManager();
		
        $entities = $em->getRepository($this->className)->getNewsByStateAdmin($state);
		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($request->getLocale(), $state);
		
        return $this->render('news/NewsAdmin/indexState.html.twig', array(
            'entities' => $entities,
			'state' => $state
        ));
    }

    public function WYSIWYGUploadFileAction(Request $request, APImgSize $imgSize)
    {
		return $this->WYSIWYGUploadFileGenericAction($request, $imgSize, new News());
    }

	public function reloadThemeByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = array();
		
		if(!empty($language))
		{
			$themes = $em->getRepository(Theme::class)->findByLanguage($language, array('title' => 'ASC'));
			
			$currentLanguagesWebsite = array("fr", "en", "es");
			if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite))
				$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => 'en'));

			$states = $em->getRepository(State::class)->findByLanguage($language, array('title' => 'ASC'));
			$licences = $em->getRepository(Licence::class)->findByLanguage($language, array('title' => 'ASC'));
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->findAll();
			$states = $em->getRepository(State::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
		}

		$themeArray = array();
		$stateArray = array();
		$licenceArray = array();
		
		foreach($themes as $theme)
		{
			$themeArray[] = array("id" => $theme->getId(), "title" => $theme->getTitle());
		}
		$translateArray['theme'] = $themeArray;

		foreach($states as $state)
		{
			$stateArray[] = array("id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName());
		}
		$translateArray['state'] = $stateArray;

		foreach($licences as $licence)
		{
			$licenceArray[] = array("id" => $licence->getId(), "title" => $licence->getTitle());
		}
		$translateArray['licence'] = $licenceArray;
		
		$response = new Response(json_encode($translateArray));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('News_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}

	public function changeStateAction(Request $request, TranslatorInterface $translator, SessionInterface $session, $id, $state)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $request->getLocale();
		
		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);

		$entity = $em->getRepository(News::class)->find($id);
		
		$entity->setState($state);
		$em->persist($entity);
		$em->flush();

		if($state->getInternationalName() == "Validate")
			$session->getFlashBag()->add('success', $translator->trans('news.admin.NewsPublished', array(), 'validators'));
		else
			$session->getFlashBag()->add('success', $translator->trans('news.admin.NewsRefused', array(), 'validators'));
		
		return $this->redirect($this->generateUrl('News_Admin_Show', array('id' => $id)));
	}
	
	public function archiveAction($id)
	{
		return $this->archiveGenericArchive($id);
	}
	
	// The Daily Truth
	public function newTheDailyTruthAction(Request $request)
	{
		$username = getenv("THEDAILYTRUTH_EMAIL");
		$password = getenv("THEDAILYTRUTH_PASSWORD");

		$headers = array(
			'Content-Type: application/json',
			'Authorization: Basic '. base64_encode("$username:$password")
		);
	
		$curl = curl_init("http://127.0.0.1:5000/user/login_api");
	 
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	 
		$res = curl_exec($curl);
		$errors = curl_error($curl);
		curl_close($curl);
		
		$json = json_decode($res);
		
		

		$curl = curl_init("http://127.0.0.1:5000/admin/article/api/tags");
	 
		curl_setopt($curl, CURLOPT_HTTPHEADER, ["x-access-tokens: ".$json->token]);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	 
		$json_response = curl_exec($curl);
		$errors = curl_error($curl);
		curl_close($curl);

		return new Response();
		
		
		$formType = \App\Form\Type\NewsTheDailyTruthAdminType::class;
		
		$twig = 'news/NewsAdmin/newTheDailyTruth.html.twig';
		$entity = new News();
		$this->defaultValueForMappedSuperclassBase($request, $entity);
        $form = $this->createForm($formType, $entity);

        return $this->render($twig, array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
	}
	
	public function createTheDailyTruthAction()
	{
		$username = getenv("THEDAILYTRUTH_EMAIL");
		$password = getenv("THEDAILYTRUTH_PASSWORD");

		$headers = array(
			'Content-Type: application/json',
			'Authorization: Basic '. base64_encode("$username:$password")
		);
	
		$curl = curl_init("http://127.0.0.1:5000/user/login_api");
	 
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	 
		$res = curl_exec($curl);
		$errors = curl_error($curl);
		curl_close($curl);
		
		$json = json_decode($res);
		// die(var_dump($json->token));
		$data = [
			"title" => "test title",
			"text" => "Thelema (du grec ancien θέλημα : « volonté », dérivé du verbe θέλω : « vouloir, désirer ») est une doctrine ésotérique occidentale souvent considérée comme une religion ou une philosophie. Son nom est dérivé de l'abbaye de Thélème, lieu imaginaire inventé par François Rabelais dans Gargantua, dans laquelle une communauté vertueuse suit une maxime en apparence licencieuse : « Fays ce que vouldras »."
		];
	
		$curl = curl_init("http://127.0.0.1:5000/admin/article/api/new");
	 
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ["x-access-tokens: ".$json->token]);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	 
		$json_response = curl_exec($curl);
		$errors = curl_error($curl);
		curl_close($curl);
		die(var_dump($json_response, $errors));
		return new Response();
	}
	
	public function internationalizationAction(Request $request, $id)
	{
		$formType = NewsAdminType::class;
		$entity = new News();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(News::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$theme = $em->getRepository(Theme::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getTheme()->getInternationalName()]);
		$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getState()->getInternationalName()]);
		
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
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());
			
			$entity->setIllustration($illustration);
		}

		$request->setLocale($language->getAbbreviation());

		$twig = 'news/NewsAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}
}