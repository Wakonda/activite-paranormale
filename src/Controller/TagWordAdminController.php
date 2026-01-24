<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\TagWord;
use App\Entity\Tags;
use App\Entity\Language;
use App\Entity\FileManagement;
use App\Form\Type\TagWordAdminType;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/tagword')]
class TagWordAdminController extends AdminGenericController
{
	protected $entityName = 'TagWord';
	protected $className = TagWord::class;

	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "TagWord_Admin_Index"; 
	protected $showRoute = "TagWord_Admin_Show";
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
		
    	// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/', name: 'TagWord_Admin_Index')]
    public function index()
    {
		$twig = 'tags/TagWordAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'TagWord_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'tags/TagWordAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'TagWord_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = TagWordAdminType::class;
		$entity = new TagWord();

		$twig = 'tags/TagWordAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType);
    }

	#[Route('/create', name: 'TagWord_Admin_Create', requirements: ['_method' => "post"])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = TagWordAdminType::class;
		$entity = new TagWord();

		$twig = 'tags/TagWordAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }

	#[Route('/{id}/edit', name: 'TagWord_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$formType = TagWordAdminType::class;

		$twig = 'tags/TagWordAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
    }

	#[Route('/{id}/update', name: 'TagWord_Admin_Update', requirements: ['_method' => "post"])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = TagWordAdminType::class;
		
		$twig = 'tags/TagWordAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType);
    }

	#[Route('/{id}/delete', name: 'TagWord_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'TagWord_Admin_IndexDatatables', requirements: ['_method' => "get"])]
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
			 <a href='".$this->generateUrl('TagWord_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('TagWord_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/showImageSelectorColorbox', name: 'TagWord_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('TagWord_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'TagWord_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	#[Route('/autocomplete', name: 'TagWord_Admin_Autocomplete')]
	public function autocomplete(Request $request, EntityManagerInterface $em)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);

		$datas =  $em->getRepository(TagWord::class)->getAutocomplete($locale, $query);

		$results = [];

		foreach($datas as $data)
		{
			$obj = new \stdClass();
			$obj->id = $data->getId();
			$obj->text = $data->getTitle();

			$results[] = $obj;
		}

        return new JsonResponse($results);
	}

	#[Route('/internationalization/{id}', name: 'TagWord_Admin_Internationalization')]
    public function internationalization(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = TagWordAdminType::class;
		$entity = new TagWord();

		$entityToCopy = $em->getRepository(TagWord::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		
		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setWikidata($entityToCopy->getWikidata());
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

		$twig = 'tags/TagWordAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }
}