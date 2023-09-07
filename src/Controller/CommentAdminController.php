<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Comment;
use App\Entity\Language;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;

/**
 * Commentaire controller.
 *
 */
class CommentAdminController extends AdminGenericController
{
	protected $entityName = 'Comment';
	protected $className = Comment::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Comment_Admin_Index"; 
	protected $showRoute = "Comment_Admin_Show";
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $cvv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
	}
	
    /**
     * Lists all Commentaire entities.
     *
     */
    public function indexAction(EntityManagerInterface $em)
    {
        $entities = $em->getRepository(Comment::class)->findAll();

        return $this->render('comment/CommentAdmin/index.html.twig', [
            'entities' => $entities
        ]);
    }

    /**
     * Finds and displays a Commentaire entity.
     *
     */
    public function showAction(EntityManagerInterface $em, $id)
    {
        $entity = $em->getRepository(Comment::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Commentaire entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('comment/CommentAdmin/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }
	
	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGenericAction($request, $em);
		$output = $informationArray['output'];

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			$row[] =  $entity[0]->getId();
			$row[] =  $entity["user"];
			$row[] =  $entity[0]->getEmailComment();
			$row[] =  $date->doDate($request->getLocale(), $entity[0]->getDateComment());

			if($entity[0]->isApproved())
				$row[] = '<i class="fas fa-check-circle text-success" aria-hidden="true"></i>';
			elseif($entity[0]->isDenied())
				$row[] = '<i class="fas fa-times-circle text-danger" aria-hidden="true"></i>';
			else
				$row[] = '<i class="fas fa-question-circle text-primary" aria-hidden="true"></i>';
			
			$row[] = "
			 <a href='".$this->generateUrl('Comment_Admin_Show', array('id' => $entity[0]->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			";
			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

    /**
     * Deletes a Commentaire entity.
     *
     */
    public function deleteAction(Request $request, EntityManagerInterface $em, $id)
    {
        $form = $this->createDeleteForm($id);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity = $em->getRepository(Comment::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Commentaire entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('commentaire'));
    }
	
	public function countToModerateCommentsAction(EntityManagerInterface $em)
	{
		$total = $em->getRepository(Comment::class)->countAllCommentsByState(Comment::$notChecked);

		return new Response($total);
	}
	
	public function changeStateAction(EntityManagerInterface $em, $id, $state)
	{
		$entity = $em->getRepository(Comment::class)->find($id);
		
		$entity->setState($state);

		$em->persist($entity);
		$em->flush();
		
		return $this->redirect($this->generateUrl('Comment_Admin_Show', array('id' => $id)));
	}

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class)
            ->getForm()
        ;
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