<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
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
use App\Entity\FileManagement;
use App\Form\Type\WitchcraftToolAdminType;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/witchcrafttool')]
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

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/', name: 'WitchcraftTool_Admin_Index')]
    public function index()
    {
		$twig = 'witchcraft/WitchcraftToolAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'WitchcraftTool_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'witchcraft/WitchcraftToolAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'WitchcraftTool_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();

		$twig = 'witchcraft/WitchcraftToolAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'WitchcraftTool_Admin_Create', requirements: ['_method' => "post"])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();

		$twig = 'witchcraft/WitchcraftToolAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'WitchcraftTool_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = WitchcraftToolAdminType::class;

		$twig = 'witchcraft/WitchcraftToolAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'WitchcraftTool_Admin_Update', requirements: ['_method' => "post"])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WitchcraftToolAdminType::class;
		$twig = 'witchcraft/WitchcraftToolAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'WitchcraftTool_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'WitchcraftTool_Admin_IndexDatatables', requirements: ['_method' => "get"])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
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

	#[Route('/reloadlistsbylanguage', name: 'WitchcraftTool_Admin_ReloadListsByLanguage')]
	public function reloadListsByLanguage(Request $request, EntityManagerInterface $em)
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

	#[Route('/internationalization/{id}', name: 'WitchcraftTool_Admin_Internationalization')]
    public function internationalization(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = WitchcraftToolAdminType::class;
		$entity = new WitchcraftTool();

		$entityToCopy = $em->getRepository(WitchcraftTool::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		
		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setWikidata($entityToCopy->getWikidata());
		$entity->setPublicationDate($entityToCopy->getPublicationDate());

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
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	#[Route('/showImageSelectorColorbox', name: 'WitchcraftTool_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('WitchcraftTool_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'WitchcraftTool_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}
}