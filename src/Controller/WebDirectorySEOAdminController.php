<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\WebDirectorySEO;
use App\Form\Type\WebDirectorySEOAdminType;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ConstraintControllerValidator;

/**
 * WebDirectorySEO controller.
 *
 */
class WebDirectorySEOAdminController extends AdminGenericController
{
	protected $entityName = 'WebDirectorySEO';
	protected $className = WebDirectorySEO::class;

	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "WebDirectorySEO_Admin_Index"; 
	protected $showRoute = "WebDirectorySEO_Admin_Show";

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = WebDirectorySEOAdminType::class;
		$entity = new WebDirectorySEO();

		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType);
    }

    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WebDirectorySEOAdminType::class;
		$entity = new WebDirectorySEO();

		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }

    public function editAction(EntityManagerInterface $em, $id)
    {
		$formType = WebDirectorySEOAdminType::class;

		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
    }

	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WebDirectorySEOAdminType::class;
		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType);
    }

    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
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
			 <a href='".$this->generateUrl('WebDirectorySEO_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('WebDirectorySEO_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
}