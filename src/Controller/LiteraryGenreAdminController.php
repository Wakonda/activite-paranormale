<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\LiteraryGenre;
use App\Entity\Language;
use App\Entity\FileManagement;
use App\Form\Type\LiteraryGenreAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * LiteraryGenre controller.
 *
 */
class LiteraryGenreAdminController extends AdminGenericController
{
	protected $entityName = 'LiteraryGenre';
	protected $className = LiteraryGenre::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "LiteraryGenre_Admin_Index"; 
	protected $showRoute = "LiteraryGenre_Admin_Show";

	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'book/LiteraryGenreAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'book/LiteraryGenreAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = LiteraryGenreAdminType::class;
		$entity = new LiteraryGenre();

		$twig = 'book/LiteraryGenreAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = LiteraryGenreAdminType::class;
		$entity = new LiteraryGenre();

		$twig = 'book/LiteraryGenreAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(Request $request, $id)
    {
		$formType = LiteraryGenreAdminType::class;

		$twig = 'book/LiteraryGenreAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = LiteraryGenreAdminType::class;
		$twig = 'book/LiteraryGenreAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType);
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
			$row[] = "
			 <a href='".$this->generateUrl('LiteraryGenre_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('LiteraryGenre_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('LiteraryGenre_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = LiteraryGenreAdminType::class;
		$entity = new LiteraryGenre();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(LiteraryGenre::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setWikidata($entityToCopy->getWikidata());
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

		$twig = 'book/LiteraryGenreAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit']);
    }
}