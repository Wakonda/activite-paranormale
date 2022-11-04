<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Banner;
use App\Form\Type\BannerAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Banner controller.
 *
 */
class BannerAdminController extends AdminGenericController
{
	protected $entityName = 'Banner';
	protected $className = Banner::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Banner_Admin_Index"; 
	protected $showRoute = "Banner_Admin_Show";
	protected $illustrations = [["field" => "image", 'selectorFile' => 'photo_selector']];
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository(Banner::class)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'index/BannerAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'index/BannerAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = BannerAdminType::class;
		$entity = new Banner();

		$twig = 'index/BannerAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = BannerAdminType::class;
		$entity = new Banner();

		$twig = 'index/BannerAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction($id)
    {
		$formType = BannerAdminType::class;

		$twig = 'index/BannerAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = BannerAdminType::class;
		
		$twig = 'index/BannerAdmin/edit.html.twig';
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
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getAssetImagePath().$entity->getImage().'" alt="" width="100px">';
			
			if($entity->getDisplay())
				$state = '<span class="center text-success"><i class="fas fa-check" aria-hidden="true"></i></span>';
			else
				$state = '<span class="text-danger"><i class="fas fa-times" aria-hidden="true"></i></span>';
			
			$row[] = $state;
			
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			
			$row[] = "
			 <a href='".$this->generateUrl('Banner_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Banner_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
}