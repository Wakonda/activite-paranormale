<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Testimony;
use App\Entity\State;
use App\Entity\TestimonyTags;
use App\Entity\TestimonyFileManagement;
use App\Entity\Region;
use App\Entity\Language;
use App\Entity\Licence;
use App\Entity\Theme;
use App\Form\Type\TestimonyAdminType;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

#[Route('/admin/testimony')]
class TestimonyAdminController extends AdminGenericController
{
	protected $entityName = 'Testimony';
	protected $className = Testimony::class;

	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "Testimony_Admin_Index";
	protected $showRoute = "Testimony_Admin_Show";
	protected $formName = 'ap_testimony_testimonyadmintype';

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new TestimonyTags(), $entityBindded);
	}

	#[Route('/index/{state}/{display}', name: 'Testimony_Admin_Index', defaults: ['state' => null, 'display' => 1])]
    public function index()
    {
		$twig = 'testimony/TestimonyAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Testimony_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'testimony/TestimonyAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'Testimony_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = TestimonyAdminType::class;
		$entity = new Testimony();

		$twig = 'testimony/TestimonyAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'Testimony_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = TestimonyAdminType::class;
		$entity = new Testimony();

		$twig = 'testimony/TestimonyAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'Testimony_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = TestimonyAdminType::class;
		$entity = $em->getRepository($this->className)->find($id);

		$twig = 'testimony/TestimonyAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'Testimony_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = TestimonyAdminType::class;

		$twig = 'testimony/TestimonyAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'Testimony_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\TestimonyComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\TestimonyVote")->findBy(["testimony" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\TestimonyTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }
		$fms = $em->getRepository("\App\Entity\TestimonyFileManagement")->findBy(["testimony" => $id]);
		foreach($fms as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }

	#[Route('/archive/{id}', name: 'Testimony_Admin_Archive', requirements: ['id' => '\d+'])]
	public function archive(EntityManagerInterface $em, $id)
	{
		$entities = $em->getRepository(TestimonyFileManagement::class)->getAllFilesByIdClassName($id);

		$additionalFiles = [];

		foreach($entities as $entity) {
			$additionalFiles[] = $entity->getRealNameFile();
		}

		return $this->archiveGenericArchive($em, $id, $additionalFiles);
	}
	
	public function countTestimonyAction(EntityManagerInterface $em, $state)
	{
		$countTestimony = $em->getRepository(Testimony::class)->countTestimony($state);
		
		return new Response($countTestimony);
	}

	#[Route('/datatables', name: 'Testimony_Admin_IndexDatatables', methods: ['GET'])]
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
			$row[] = $entity->authorToString();
			$row[] = $entity->getState()->getTitle();
			$row[] = $date->doDate($request->getLocale(), $entity->getWritingDate());
			$row[] = "
			 <a href='".$this->generateUrl('Testimony_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('Testimony_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/change_state/{id}/{state}', name: 'Testimony_Admin_ChangeState', requirements: ['id' => '\d+'])]
	public function changeState(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $id, $state)
	{
		$language = $request->getLocale();

		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);
		$entity = $em->getRepository(Testimony::class)->find($id);

		$entity->setState($state);

		if($state->getInternationalName() == "Validate") {
			if(empty($entity->getTitle()) or empty($entity->getTheme()))
				return $this->redirect($this->generateUrl('Testimony_Admin_Edit', ['id' => $id]));
		}
		
		$em->persist($entity);
		$em->flush();
		
		if($state->getInternationalName() == "Validate")
			$this->addFlash('success', $translator->trans('testimony.admin.TestimonyPublished', [], 'validators'));
		else
			$this->addFlash('success', $translator->trans('testimony.admin.TestimonyRefused', [], 'validators'));
		
		return $this->redirect($this->generateUrl('Testimony_Admin_Show', ['id' => $id]));
	}

	#[Route('/delete_multiple', name: 'Testimony_Admin_DeleteMultiple')]
	public function deleteMultiple(Request $request, EntityManagerInterface $em)
	{
		$ids = json_decode($request->request->get("ids"));

		$entities = $em->getRepository($this->className)->findBy(['id' => $ids]);

		foreach($entities as $entity)
			$em->remove($entity);

		$em->flush();

		return new Response();
	}

	#[Route('/reloadlistsbylanguage', name: 'Testimony_Admin_ReloadListsByLanguage')]
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
			$countries = $em->getRepository(Region::class)->getCountryByLanguage($language->getAbbreviation())->getQuery()->getResult();
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList(null, $request->getLocale());
			$states = $em->getRepository(State::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
			$countries = $em->getRepository(Region::class)->findAll();
		}

		$themeArray = [];
		$stateArray = [];
		$licenceArray = [];
		$countryArray = [];
		
		foreach($themes as $theme)
			$themeArray[] = ["id" => $theme["id"], "title" => $theme["title"]];

		$translateArray['theme'] = $themeArray;

		foreach($states as $state)
			$stateArray[] = ["id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName()];

		$translateArray['state'] = $stateArray;

		foreach($licences as $licence)
			$licenceArray[] = ["id" => $licence->getId(), "title" => $licence->getTitle()];

		$translateArray['licence'] = $licenceArray;

		foreach($countries as $country)
			$countryArray[] = ["id" => $country->getInternationalName(), "title" => $country->getTitle()];

		$translateArray['country'] = $countryArray;

		return new JsonResponse($translateArray);
	}
}