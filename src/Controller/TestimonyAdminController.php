<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Testimony;
use App\Entity\State;
use App\Entity\TestimonyTags;
use App\Entity\TestimonyFileManagement;
use App\Form\Type\TestimonyAdminType;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;
use App\Service\TagsManagingGeneric;

class TestimonyAdminController extends AdminGenericController
{
	protected $entityName = 'Testimony';
	protected $className = Testimony::class;

	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "Testimony_Admin_Index";
	protected $showRoute = "Testimony_Admin_Show";
	protected $formName = 'ap_testimony_testimonyadmintype';

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidationAction($form, $entityBindded)
	{
		(new TagsManagingGeneric($this->getDoctrine()->getManager()))->saveTags($form, $this->className, $this->entityName, new TestimonyTags(), $entityBindded);
	}

    public function indexAction()
    {
		$twig = 'testimony/TestimonyAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'testimony/TestimonyAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = TestimonyAdminType::class;
		$entity = new Testimony();

		$twig = 'testimony/TestimonyAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = TestimonyAdminType::class;
		$entity = new Testimony();

		$twig = 'testimony/TestimonyAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }

    public function editAction(Request $request, $id)
    {
		$formType = TestimonyAdminType::class;
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);

		$twig = 'testimony/TestimonyAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = TestimonyAdminType::class;

		$twig = 'testimony/TestimonyAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		$em = $this->getDoctrine()->getManager();
		/*$comments = $em->getRepository("\App\Entity\TestimonyComment")->findBy(["entity" => $id]);
		foreach($comments as $entity) {$em->remove($entity); }
		$votes = $em->getRepository("\App\Entity\TestimonyVote")->findBy(["testimony" => $id]);
		foreach($votes as $entity) {$em->remove($entity); }
		$tags = $em->getRepository("\App\Entity\TestimonyTags")->findBy(["entity" => $id]);
		foreach($tags as $entity) {$em->remove($entity); }
		$fms = $em->getRepository("\App\Entity\TestimonyFileManagement")->findBy(["testimony" => $id]);
		foreach($fms as $entity) {$em->remove($entity); }*/

		return $this->deleteGenericAction($id);
    }
	
	public function archiveAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entities = $em->getRepository(TestimonyFileManagement::class)->getAllFilesForTestimonyByIdClassName($id);

		$additionalFiles = [];

		foreach($entities as $entity) {
			$additionalFiles[] = $entity->getRealNameFile();
		}

		return $this->archiveGenericArchive($id, $additionalFiles);
	}
	
	public function countTestimonyAction($state)
	{
		$em = $this->getDoctrine()->getManager();
		$countTestimony = $em->getRepository(Testimony::class)->countTestimony($state);
		
		return new Response($countTestimony);
	}

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
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

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
	
	public function changeStateAction(Request $request, TranslatorInterface $translator, $id, $state)
	{
		$em = $this->getDoctrine()->getManager();
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
}