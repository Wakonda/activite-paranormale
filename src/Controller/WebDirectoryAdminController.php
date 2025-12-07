<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\WebDirectory;
use App\Entity\Language;
use App\Entity\Licence;
use App\Entity\State;
use App\Form\Type\WebDirectoryAdminType;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/directory')]
class WebDirectoryAdminController extends AdminGenericController
{
	protected $entityName = 'WebDirectory';
	protected $className = WebDirectory::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "WebDirectory_Admin_Index"; 
	protected $showRoute = "WebDirectory_Admin_Show";
	protected $formName = 'ap_webdirectory_webdirectoryadmintype';

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

	#[Route('/', name: 'WebDirectory_Admin_Index')]
    public function index()
    {
		$twig = 'webdirectory/WebDirectoryAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'WebDirectory_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'webdirectory/WebDirectoryAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'WebDirectory_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = WebDirectoryAdminType::class;
		$entity = new WebDirectory();

		$twig = 'webdirectory/WebDirectoryAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'WebDirectory_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WebDirectoryAdminType::class;
		$entity = new WebDirectory();

		$twig = 'webdirectory/WebDirectoryAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'WebDirectory_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = WebDirectoryAdminType::class;

		$twig = 'webdirectory/WebDirectoryAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'WebDirectory_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WebDirectoryAdminType::class;
		$twig = 'webdirectory/WebDirectoryAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'WebDirectory_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'WebDirectory_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<a href="'.$entity->getLink().'">'.$entity->getLink().'</a>';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getAssetImagePath().$entity->getLogo().'" alt="" width="100px">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = $entity->getState()->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('WebDirectory_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('WebDirectory_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/reloadlistsbylanguage', name: 'WebDirectory_Admin_ReloadListsByLanguage')]
	public function reloadListsByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language)) {
			$licences = $em->getRepository(Licence::class)->findByLanguage($language, array('title' => 'ASC'));
			$states = $em->getRepository(State::class)->findByLanguage($language, ['title' => 'ASC']);
		} else {
			$licences = $em->getRepository(Licence::class)->findAll();
			$states = $em->getRepository(State::class)->findAll();
		}

		$licenceArray = [];
		$stateArray = [];

		foreach($licences as $licence)
		{
			$licenceArray[] = array("id" => $licence->getId(), "title" => $licence->getTitle());
		}
		$translateArray['licence'] = $licenceArray;

		foreach($states as $state)
			$stateArray[] = ["id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName()];

		$translateArray['state'] = $stateArray;

		return new JsonResponse($translateArray);
	}

	#[Route('/internationalization/{id}', name: 'WebDirectory_Admin_Internationalization')]
    public function internationalization(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = WebDirectoryAdminType::class;
		$entity = new WebDirectory();

		$entityToCopy = $em->getRepository(WebDirectory::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		
		$websiteLanguage = null;
		
		if(!empty($entityToCopy->getWebsiteLanguage()))
			$websiteLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $entityToCopy->getWebsiteLanguage()->getAbbreviation()]);
		
		$licence = null;
		
		if(!empty($entityToCopy->getLicence()))
			$licence = $em->getRepository(Licence::class)->findOneBy(["internationalName" => $entityToCopy->getLicence()->getInternationalName(), "language" => $language]);

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setLink($entityToCopy->getLink());
		$entity->setLanguage($language);
		$entity->setLogo($entityToCopy->getLogo());
		$entity->setLicence($licence);
		$entity->setWebsiteLanguage($websiteLanguage);
		$entity->setSocialNetwork(($entityToCopy->getSocialNetwork()));
		$entity->setFoundedYear($entityToCopy->getFoundedYear());
		$entity->setDefunctYear($entityToCopy->getDefunctYear());
		$entity->setWikidata($entityToCopy->getWikidata());

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

		$twig = 'webdirectory/WebDirectoryAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	#[Route('/showImageSelectorColorbox', name: 'WebDirectory_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('WebDirectory_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'WebDirectory_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	#[Route('/wikidata', name: 'WebDirectory_Admin_Wikidata')]
	public function wikidata(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");

		$res = $wikidata->getWebDirectoryDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}

	public function countByStateAction(EntityManagerInterface $em, $state)
	{
		$countByStateAdmin = $em->getRepository($this->className)->countByStateAdmin($state);
		return new Response($countByStateAdmin);
	}

	#[Route('/change_state/{id}/{state}', name: 'WebDirectory_Admin_ChangeState', requirements: ['id' => '\d+'])]
	public function changeState(Request $request, EntityManagerInterface $em, $id, $state)
	{
		$language = $request->getLocale();
		
		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);
		$entity = $em->getRepository($this->className)->find($id);
		
		$entity->setState($state);
		$em->persist($entity);
		$em->flush();

		$formType = WebDirectoryAdminType::class;

		$twig = 'webdirectory/WebDirectoryAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
	}

	#[Route('/delete_multiple', name: 'WebDirectory_Admin_DeleteMultiple')]
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