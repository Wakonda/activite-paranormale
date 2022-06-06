<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\UsefulLink;
use App\Form\Type\UsefulLinkAdminType;
use App\Service\ConstraintControllerValidator;

class UsefulLinkAdminController extends AdminGenericController
{
	protected $entityName = 'UsefulLink';
	protected $className = UsefulLink::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "UsefulLink_Admin_Index"; 
	protected $showRoute = "UsefulLink_Admin_Show";

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'usefullink/UsefulLinkAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'usefullink/UsefulLinkAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = UsefulLinkAdminType::class;
		$entity = new UsefulLink();

		$twig = 'usefullink/UsefulLinkAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = UsefulLinkAdminType::class;
		$entity = new UsefulLink();

		$twig = 'usefullink/UsefulLinkAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction($id)
    {
		$formType = UsefulLinkAdminType::class;

		$twig = 'usefullink/UsefulLinkAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = UsefulLinkAdminType::class;

		$twig = 'usefullink/UsefulLinkAdmin/edit.html.twig';
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
			$links = [];

			if(!empty($linkArray = $entity->getLinks())) {
				$i = 0;
				foreach(json_decode($linkArray) as $data) {
					if(!empty($data->title))
						$links[] = '<li><a href="'.$data->url.'">'.$data->title.'</a></li>';
					else
						$links[] = '<li><a href="'.$data->url.'">'.$data->url.'</a></li>';
					
					if($i == 4) {
						$links[] = "<li>...</li>";
						break;
					}
					
					$i++;
				}
			}

			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<ul>'.implode("", $links).'</ul>';
			$row[] = "
			 <a href='".$this->generateUrl('UsefulLink_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('UsefulLink_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function generateStoreLinkAction(Request $request)
	{
		$form = $this->createForm(\App\Form\Type\GenerateLinkStoreAdminType::class);
		$partnerId = \App\Entity\Stores\Store::partnerId;
		
		return $this->render('usefullink/UsefulLinkAdmin/generateLinkStore.html.twig', array('form' => $form->createView(), "partnerId" => $partnerId));
	}
}