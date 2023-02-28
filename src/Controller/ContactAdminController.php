<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Contact;
use App\Form\Type\ContactAdminType;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;

/**
 * Contact controller.
 *
 */
class ContactAdminController extends AdminGenericController
{
	protected $entityName = 'Contact';
	protected $className = Contact::class;

	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "Contact_Admin_Index"; 
	protected $showRoute = "Contact_Admin_Show";

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    /**
     * Lists all Contact entities.
     *
     */
    public function indexAction()
    {
		$twig = 'contact/ContactAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }

    /**
     * Finds and displays a Contact entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->className)->find($id);

        if (!$entity)
            throw $this->createNotFoundException('Unable to find Contact entity.');

		$entity->setStateContact(1);
		$em->persist($entity);
		$em->flush();

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('contact/ContactAdmin/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

	/**
	 * Count non read message contact
	 *
	 */
	 public function countNonReadAction()
	 {
		$em = $this->getDoctrine()->getManager();
		$countNonRead = $em->getRepository($this->className)->count(['stateContact' => '0']));
		return new Response($countNonRead);
	 }
	
    /**
     * Displays a form to create a new Contact entity.
     *
     */
    public function newAction(Request $request)
    {
        $entity = new Contact();
        $form = $this->createForm(new ContactAdminType($request->getLocale()), $entity);

        return $this->render('contact/ContactAdmin/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    /**
     * Creates a new Contact entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Contact();
        $form = $this->createForm(new ContactAdminType($request->getLocale()), $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('Contact_Admin_Show', ['id' => $entity->getId()]));
        }

        return $this->render('contact/ContactAdmin/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    /**
     * Displays a form to edit an existing Contact entity.
     *
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->className)->find($id);

        if (!$entity)
            throw $this->createNotFoundException('Unable to find Contact entity.');

        $editForm = $this->createForm(new ContactAdminType($request->getLocale()), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('contact/ContactAdmin/edit.html.twig', [
            'entity' => $entity,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Edits an existing Contact entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->className)->find($id);

        if (!$entity)
            throw $this->createNotFoundException('Unable to find Contact entity.');

        $editForm   = $this->createForm(new ContactAdminType($request->getLocale()), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('contact_edit', ['id' => $id]));
        }

        return $this->render('contact/ContactAdmin/edit.html.twig', [
            'entity' => $entity,
            'edit' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Contact entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository($this->className)->find($id);

		if (!$entity)
			throw $this->createNotFoundException('Unable to find Contact entity.');

		$em->remove($entity);
		$em->flush();

        return $this->redirect($this->generateUrl('Contact_Admin_Index'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(['id' => $id])
            ->add('id', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class)
            ->getForm()
        ;
    }

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] =  $entity->getId();
			$row[] =  $entity->getPseudoContact();
			$row[] =  $date->doDate($request->getLocale(), $entity->getDateContact());
			$row[] =  $entity->getSubjectContact();

			if($entity->getStateContact())
				$state = '<span class="text-success"><i class="fas fa-check"></i></span>';
			else
				$state = '<span class="text-danger"><i class="fas fa-times"></i></span>';

			$row[] =  $state;

			$delete = "<a onclick=\"return confirm('".$translator->trans('admin.index.Show', [], 'validators')."')\" href='".$this->generateUrl('Contact_Admin_Delete', ['id' => $entity->getId()])."'><i class='fas fa-trash'></i> ".$translator->trans('admin.general.Delete', [], 'validators')."</a><br>";

			$row[] = "<a href='".$this->generateUrl('Contact_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>${delete}";
			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
}