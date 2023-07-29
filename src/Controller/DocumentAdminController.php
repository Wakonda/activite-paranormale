<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Document;
use App\Entity\DocumentFamily;
use App\Entity\DocumentTags;
use App\Entity\Language;
use App\Entity\Licence;
use App\Entity\State;
use App\Entity\Theme;
use App\Form\Type\DocumentAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

/**
 * Document controller.
 *
 */
class DocumentAdminController extends AdminGenericController
{
	protected $entityName = 'Document';
	protected $className = Document::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Document_Admin_Index"; 
	protected $showRoute = "Document_Admin_Show";
	protected $formName = "ap_document_documentadmintype";
	protected $illustrations = [['field' => 'pdfDoc', 'selectorFile' => 'photo_selector']];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
		
		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
		(new TagsManagingGeneric($em))->saveTags($form, $this->className, $this->entityName, new DocumentTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'document/DocumentAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'document/DocumentAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = DocumentAdminType::class;
		$entity = new Document();

		$twig = 'document/DocumentAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = DocumentAdminType::class;
		$entity = new Document();

		$twig = 'document/DocumentAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = DocumentAdminType::class;

		$twig = 'document/DocumentAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = DocumentAdminType::class;
		
		$twig = 'document/DocumentAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository("\App\Entity\DocumentComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\DocumentTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }

		return $this->deleteGenericAction($em, $id);
    }
	
	public function archiveAction(EntityManagerInterface $em, $id)
	{
		return $this->archiveGenericArchive($em, $id);
	}

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			if($entity->getArchive())
				$row["DT_RowClass"] = "deleted";
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			
			$authorArray = [];
			
			foreach($entity->getAuthorDocumentBiographies() as $authorDocumentBiography)
			{
				$authorArray[] = $authorDocumentBiography->getTitle();
			}
			
			$row[] = implode(", ", $authorArray);
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Document_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Document_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadDocumentFamilyByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language))
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList($language->getAbbreviation(), $request->getLocale());
			$documentFamilies = $em->getRepository(DocumentFamily::class)->findByLanguage($language, array('title' => 'ASC'));
			
			$currentLanguagesWebsite = explode(",", $_ENV["LANGUAGES"]);
			if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite))
				$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => 'en'));

			$licences = $em->getRepository(Licence::class)->findByLanguage($language, array('title' => 'ASC'));
			$states = $em->getRepository(State::class)->findByLanguage($language, array('title' => 'ASC'));
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->getByLanguageForList(null, $request->getLocale());
			$documentFamilies = $em->getRepository(DocumentFamily::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
			$states = $em->getRepository(State::class)->findAll();
		}

		$themeArray = [];
		$stateArray = [];
		$licenceArray = [];
		
		foreach($themes as $theme)
			$themeArray[] = ["id" => $theme["id"], "title" => $theme["title"]];

		$translateArray['theme'] = $themeArray;
		
		foreach($licences as $licence)
		{
			$licenceArray[] = array("id" => $licence->getId(), "title" => $licence->getTitle());
		}
		$translateArray['licence'] = $licenceArray;

		foreach($states as $state)
		{
			$stateArray[] = array("id" => $state->getId(), "title" => $state->getTitle(), 'intl' => $state->getInternationalName());
		}
		$translateArray['state'] = $stateArray;

		$documentFamilyArray = [];
		
		foreach($documentFamilies as $documentFamily)
		{
			$documentFamilyArray[] = array("id" => $documentFamily->getId(), "title" => $documentFamily->getTitle());
		}
		$translateArray['documentFamily'] = $documentFamilyArray;

		return new JsonResponse($translateArray);
	}
}