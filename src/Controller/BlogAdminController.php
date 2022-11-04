<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Blog;
use App\Form\Type\BlogAdminType;
use App\Service\ConstraintControllerValidator;

class BlogAdminController extends AdminGenericController
{
	protected $entityName = 'Blog';
	protected $className = Blog::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Blog_Admin_Index"; 
	protected $showRoute = "Blog_Admin_Show";
	protected $illustrations = [["field" => "banner", 'selectorFile' => 'photo_selector']];

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'blog/BlogAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'blog/BlogAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = BlogAdminType::class;
		$entity = new Blog();

		$twig = 'blog/BlogAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = BlogAdminType::class;
		$entity = new Blog();

		$twig = 'blog/BlogAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction($id)
    {
		$formType = BlogAdminType::class;

		$twig = 'blog/BlogAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = BlogAdminType::class;

		$twig = 'blog/BlogAdmin/edit.html.twig';
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
			$row[] = $entity->getLink();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Blog_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Blog_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
}