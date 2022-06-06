<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
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
		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }

    public function showAction($id)
    {
		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = WebDirectorySEOAdminType::class;
		$entity = new WebDirectorySEO();

		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType);
    }

    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WebDirectorySEOAdminType::class;
		$entity = new WebDirectorySEO();

		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }

    public function editAction($id)
    {
		$formType = WebDirectorySEOAdminType::class;

		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }

	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WebDirectorySEOAdminType::class;
		$twig = 'webdirectoryseo/WebDirectorySEOAdmin/edit.html.twig';

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
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('WebDirectorySEO_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('WebDirectorySEO_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
}