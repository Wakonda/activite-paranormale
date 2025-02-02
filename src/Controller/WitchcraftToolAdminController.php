<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
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
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		//Default values
		$licence = $em->getRepository(Licence::class)->findOneBy(["title" => "CC-BY-NC-ND 3.0", "language" => $entityBindded->getLanguage()]);
		$state = $em->getRepository(State::class)->findOneBy(["internationalName" => "Validate", "language" => $entityBindded->getLanguage()]);
		$entityBindded->setLicence($licence);
		$entityBindded->setState($state);
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'witchcraft/WitchcraftToolAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'witchcraft/WitchcraftToolAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();

		$twig = 'witchcraft/WitchcraftToolAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();

		$twig = 'witchcraft/WitchcraftToolAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = WitchcraftToolAdminType::class;

		$twig = 'witchcraft/WitchcraftToolAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WitchcraftToolAdminType::class;
		$twig = 'witchcraft/WitchcraftToolAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGenericAction($em, $id);
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
			 <a href='".$this->generateUrl('WitchcraftTool_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('WitchcraftTool_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadListsByLanguageAction(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language))
			$themes = $em->getRepository(WitchcraftThemeTool::class)->findByLanguage($language, array('title' => 'ASC'));
		else
			$themes = $em->getRepository(WitchcraftThemeTool::class)->findAll();

		$themeArray = [];

		foreach($themes as $theme)
		{
			$themeArray[] = array("id" => $theme->getId(), "title" => $theme->getTitle());
		}
		$translateArray['theme'] = $themeArray;

		return new JsonResponse($translateArray);
	}
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();

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

		$twig = 'witchcraft/WitchcraftToolAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('WitchcraftTool_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGenericAction($request, $em);
	}
}