<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\WitchcraftTool;
use App\Entity\Theme;
use App\Entity\Licence;
use App\Entity\State;
use App\Entity\WitchcraftThemeTool;
use App\Entity\Language;
use App\Form\Type\WitchcraftToolAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * WitchcraftTool controller.
 *
 */
class WitchcraftToolAdminController extends AdminGenericController
{
	protected $entityName = 'WitchcraftTool';
	protected $className = WitchcraftTool::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "WitchcraftTool_Admin_Index"; 
	protected $showRoute = "WitchcraftTool_Admin_Show";
	protected $formName = 'ap_witchcraft_witchcrafttooladmintype';
	protected $illustrations = [['field' => 'photo', 'selectorFile' => 'photo_selector']];
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));

		//Default values
		$theme = $em->getRepository(Theme::class)->findOneBy(["language" => $entityBindded->getLanguage(), "internationalName" => "magic"]);
		$licence = $em->getRepository(Licence::class)->findOneBy(["title" => "CC-BY-NC-ND 3.0", "language" => $entityBindded->getLanguage()]);
		$state = $em->getRepository(State::class)->findOneBy(["internationalName" => "Validate", "language" => $entityBindded->getLanguage()]);
		$entityBindded->setTheme($theme);
		$entityBindded->setLicence($licence);
		$entityBindded->setState($state);
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'witchcraft/WitchcraftToolAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'witchcraft/WitchcraftToolAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();

		$twig = 'witchcraft/WitchcraftToolAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();

		$twig = 'witchcraft/WitchcraftToolAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = WitchcraftToolAdminType::class;

		$twig = 'witchcraft/WitchcraftToolAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WitchcraftToolAdminType::class;
		$twig = 'witchcraft/WitchcraftToolAdmin/edit.html.twig';

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
			$row = array();
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('WitchcraftTool_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('WitchcraftTool_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadListsByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = array();
		
		if(!empty($language))
			$themes = $em->getRepository(WitchcraftThemeTool::class)->findByLanguage($language, array('title' => 'ASC'));
		else
			$themes = $em->getRepository(WitchcraftThemeTool::class)->findAll();

		$themeArray = array();

		foreach($themes as $theme)
		{
			$themeArray[] = array("id" => $theme->getId(), "title" => $theme->getTitle());
		}
		$translateArray['theme'] = $themeArray;

		return new JsonResponse($translateArray);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(WitchcraftTool::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		
		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setText($entityToCopy->getText());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setWikidata($entityToCopy->getWikidata());
		$entity->setPublicationDate($entityToCopy->getPublicationDate());
		
		$witchcraftThemeTool = null;
		
		if(!empty($entityToCopy->getWitchcraftThemeTool()))
			$witchcraftThemeTool = $em->getRepository(WitchcraftThemeTool::class)->findOneBy(["internationalName" => $entityToCopy->getWitchcraftThemeTool()->getInternationalName(), "language" => $language]);
		
		$entity->setWitchcraftThemeTool($witchcraftThemeTool);
		
		$state = null;
		
		if(!empty($entityToCopy->getState()))
			$state = $em->getRepository(State::class)->findOneBy(["internationalName" => $entityToCopy->getState()->getInternationalName(), "language" => $language]);
		
		$entity->setState($state);

		$entity->setSource($entityToCopy->getSource());
		$entity->setLanguage($language);

		$twig = 'witchcraft/WitchcraftToolAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('WitchcraftTool_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}
}