<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\CreepyStory;
use App\Entity\CreepyStoryTags;
use App\Entity\Language;
use App\Entity\Licence;
use App\Entity\State;
use App\Entity\Theme;
use App\Entity\FileManagement;
use App\Form\Type\CreepyStoryAdminType;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

#[Route('/admin/creepystory')]
class CreepyStoryAdminController extends AdminGenericController
{
	protected $entityName = 'CreepyStory';
	protected $className = CreepyStory::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "CreepyStory_Admin_Index"; 
	protected $showRoute = "CreepyStory_Admin_Show";
	protected $formName = "ap_creepystory_creepystoryadmintype";
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new CreepyStoryTags(), $entityBindded);
	}

	#[Route('/', name: 'CreepyStory_Admin_Index')]
    public function index()
    {
		$twig = 'creepyStory/CreepyStoryAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'CreepyStory_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'creepyStory/CreepyStoryAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'CreepyStory_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = CreepyStoryAdminType::class;
		$entity = new CreepyStory();

		$twig = 'creepyStory/CreepyStoryAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'CreepyStory_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = CreepyStoryAdminType::class;
		$entity = new CreepyStory();

		$twig = 'creepyStory/CreepyStoryAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'CreepyStory_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = CreepyStoryAdminType::class;

		$twig = 'creepyStory/CreepyStoryAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'CreepyStory_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = CreepyStoryAdminType::class;
		$twig = 'creepyStory/CreepyStoryAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'CreepyStory_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$tags = $em->getRepository(CreepyStoryTags::class)->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'CreepyStory_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $date->doDate($request->getLocale(), $entity->getPublicationDate());
			$row[] = "
			 <a href='".$this->generateUrl('CreepyStory_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('CreepyStory_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/showImageSelectorColorbox', name: 'CreepyStory_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('CreepyStory_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'CreepyStory_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	#[Route('/internationalization/{id}', name: 'CreepyStory_Admin_Internationalization')]
	public function internationalization(Request $request, EntityManagerInterface $em, $id)
	{
		$formType = CreepyStoryAdminType::class;
		$entity = new CreepyStory();

		$entityToCopy = $em->getRepository(CreepyStory::class)->find($id);
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
			$illustration->setRealNameFile($ci->getRealNameFile());
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());
			
			$entity->setIllustration($illustration);
		}

		$request->setLocale($language->getAbbreviation());

		$twig = 'creepyStory/CreepyStoryAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}
}