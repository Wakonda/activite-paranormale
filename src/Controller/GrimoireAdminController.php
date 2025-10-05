<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Grimoire;
use App\Entity\SurThemeGrimoire;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\FileManagement;
use App\Form\Type\GrimoireAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\APImgSize;

#[Route('/admin/grimoire')]
class GrimoireAdminController extends AdminGenericController
{
	protected $entityName = 'Grimoire';
	protected $className = Grimoire::class;

	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "Grimoire_Admin_Index"; 
	protected $showRoute = "Grimoire_Admin_Show";
	protected $formName = "ap_witchcraft_grimoireadmintype";
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Validate', 'language' => $entityBindded->getLanguage()]);

		if(empty($state)) {
			$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => "en"]);
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Validate', 'language' => $language]);
		}

		$entityBindded->setState($state);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/index/{state}/{display}', name: 'Grimoire_Admin_Index', defaults: ['state' => null, 'display' => 1])]
    public function index()
    {
		$twig = 'witchcraft/GrimoireAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Grimoire_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'witchcraft/GrimoireAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'Grimoire_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = GrimoireAdminType::class;
		$entity = new Grimoire();

		$twig = 'witchcraft/GrimoireAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'Grimoire_Admin_Create', requirements: ['_method' => "post"])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = GrimoireAdminType::class;
		$entity = new Grimoire();

		$twig = 'witchcraft/GrimoireAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'Grimoire_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = GrimoireAdminType::class;

		$twig = 'witchcraft/GrimoireAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'Grimoire_Admin_Update', requirements: ['_method' => "post"])]
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = GrimoireAdminType::class;
		$twig = 'witchcraft/GrimoireAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'Grimoire_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$comments = $em->getRepository("\App\Entity\GrimoireComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\GrimoireVote")->findBy(["entity" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }

		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'Grimoire_Admin_IndexDatatables', requirements: ['_method' => "get"])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];
		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = !empty($entity->getSurTheme()) ? $entity->getSurTheme()->getTitle() : null;

			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => $entity->getState()->getInternationalName(), 'language' => $language]);
			$row[] =  $state->getTitle();

			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Grimoire_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Grimoire_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	#[Route('/change_state/{id}/{state}', name: 'Grimoire_Admin_ChangeState', requirements: ['id' => "\d+"])]
	public function changeStateAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $id, $state)
	{
		$language = $request->getLocale();

		$state = $em->getRepository(State::class)->getStateByLanguageAndInternationalName($language, $state);

		$entity = $em->getRepository($this->className)->find($id);

		$entity->setState($state);
		
		if($state->getInternationalName() == "Validate") {
			if(empty($entity->getSurTheme()))
				return $this->redirect($this->generateUrl('Grimoire_Admin_Edit', ['id' => $id]));
		}

		$em->persist($entity);
		$em->flush();

		if($state->getInternationalName() == "Validate")
			$this->addFlash('success', $translator->trans('grimoire.admin.RitualPublished', [], 'validators'));
		else
			$this->addFlash('success', $translator->trans('grimoire.admin.RitualRefused', [], 'validators'));

		return $this->redirect($this->generateUrl('Grimoire_Admin_Show', ['id' => $id]));
	}

	public function countByStateAction(EntityManagerInterface $em, $state)
	{
		$countByStateAdmin = $em->getRepository($this->className)->countByStateAdmin($state);
		return new Response($countByStateAdmin);
	}

	#[Route('/reloadlistsbylanguage', name: 'Grimoire_Admin_ReloadListsByLanguage')]
	public function reloadListsByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];

		if(!empty($language))
			$grimoires = $em->getRepository(SurThemeGrimoire::class)->findByLanguage($language, ['title' => 'ASC']);
		else
			$grimoires = $em->getRepository(SurThemeGrimoire::class)->findByLanguage($language, ['title' => 'ASC']);

		$grimoireArray = [];

		foreach($grimoires as $grimoire)
		{
			if(!empty($grimoire->getParentTheme()))
				$grimoireArray[$grimoire->getParentTheme()->getTitle()][] = ["id" => $grimoire->getId(), "title" => $grimoire->getTitle()];
		}
		$translateArray['grimoire'] = $grimoireArray;

		$response = new Response(json_encode($translateArray));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	protected function defaultValueForMappedSuperclassBase(Request $request, EntityManagerInterface $em, $entity)
	{
		$language = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $request->getLocale()]);
		$entity->setLanguage($language);
	}

	#[Route('/archive/{id}', name: 'Grimoire_Admin_Archive', requirements: ['id' => "\d+"])]
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
	}

	#[Route('/wysiwyg_uploadfile', name: 'Grimoire_Admin_WYSIWYG_UploadFile')]
    public function WYSIWYGUploadFileAction(Request $request, APImgSize $imgSize)
    {
		return $this->WYSIWYGUploadFileGenericAction($request, $imgSize, new Grimoire());
    }

	#[Route('/showImageSelectorColorbox', name: 'Grimoire_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('Grimoire_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'Grimoire_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorboxAction(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	#[Route('/internationalization/{id}', name: 'Grimoire_Admin_Internationalization')]
	public function internationalization(Request $request, EntityManagerInterface $em, $id)
	{
		$formType = GrimoireAdminType::class;
		$entity = new Grimoire();

		$entityToCopy = $em->getRepository(Grimoire::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$surthemegrimoire = $em->getRepository(SurThemeGrimoire::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getSurTheme()->getInternationalName()]);
		$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => $entityToCopy->getState()->getInternationalName()]);

		if(empty($state)) {
			$defaultLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
			$state = $em->getRepository(State::class)->findOneBy(["language" => $defaultLanguage, "internationalName" => "Validate"]);
		}

		$entity->setState($state);

		if(!empty($surthemegrimoire))
			$entity->setSurTheme($surthemegrimoire);

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

		$twig = 'witchcraft/GrimoireAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ["locale" => $language->getAbbreviation(), 'action' => 'new']);
	}

	#[Route('/delete_multiple', name: 'Grimoire_Admin_DeleteMultiple')]
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