<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
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

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
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
    public function showAction(EntityManagerInterface $em, $id)
    {
        $entity = $em->getRepository($this->className)->find($id);

        if (!$entity)
            throw $this->createNotFoundException('Unable to find Contact entity.');

		if(!$entity->isPrivateMessage()) {
			$entity->setStateContact(1);
			$em->persist($entity);
			$em->flush();
		}

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('contact/ContactAdmin/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

	 public function countNonReadAction(EntityManagerInterface $em)
	 {
		$countNonRead = $em->getRepository($this->className)->count(['stateContact' => '0']);
		return new Response($countNonRead);
	 }

    /**
     * Deletes a Contact entity.
     *
     */
    public function deleteAction(Request $request, EntityManagerInterface $em, $id)
    {
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

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGenericAction($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] =  $entity->getId();
			$row[] =  $entity->getPseudoContact();
			$row[] =  $date->doDate($request->getLocale(), $entity->getDateContact());
			$row[] =  $entity->getSubjectContact();

			if($entity->getStateContact())
				$state = '<i class="fas fa-check text-success""></i>';
			else
				$state = '<i class="fas fa-times text-danger"></i>';

			$row[] =  $state;

			$delete = "<a onclick=\"return confirm('".$translator->trans('admin.show.ReallyWantRemoveDatas', [], 'validators')."')\" href='".$this->generateUrl('Contact_Admin_Delete', ['id' => $entity->getId()])."'><i class='fas fa-trash'></i> ".$translator->trans('admin.general.Delete', [], 'validators')."</a><br>";

			$row[] = "<a href='".$this->generateUrl('Contact_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>{$delete}";
			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

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