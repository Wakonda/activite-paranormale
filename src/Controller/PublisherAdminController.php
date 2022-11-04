<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Publisher;
use App\Form\Type\PublisherAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Publisher controller.
 *
 */
class PublisherAdminController extends AdminGenericController
{
	protected $entityName = 'Publisher';
	protected $className = Publisher::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Publisher_Admin_Index"; 
	protected $showRoute = "Publisher_Admin_Show";
	protected $illustrations = [["field" => "photo", 'selectorFile' => 'photo_selector']];
	
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
		$twig = 'book/PublisherAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'book/PublisherAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = PublisherAdminType::class;
		$entity = new Publisher();

		$twig = 'book/PublisherAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = PublisherAdminType::class;
		$entity = new Publisher();

		$twig = 'book/PublisherAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(Request $request, $id)
    {
		$formType = PublisherAdminType::class;

		$twig = 'book/PublisherAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = PublisherAdminType::class;
		$twig = 'book/PublisherAdmin/edit.html.twig';

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
			 <a href='".$this->generateUrl('Publisher_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Publisher_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
}