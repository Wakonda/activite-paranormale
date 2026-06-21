<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Service\ConstraintControllerValidator;
use App\Entity\LoginHistory;
use App\Service\APDate;

#[Route('/admin/login_history')]
class LoginHistoryAdminController extends AdminGenericController
{
	protected $entityName = 'LoginHistory';
	protected $className = LoginHistory::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "LoginHistory_Admin_Index"; 
	protected $showRoute = "LoginHistory_Admin_Show";

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/', name: 'LoginHistory_Admin_Index')]
    public function indexAction()
    {
		$twig = 'user/LoginHistoryAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'LoginHistory_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'user/LoginHistoryAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/datatables', name: 'LoginHistory_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APDate $date)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getAttemptedIdentifier();
			
			if($entity->isSuccess())
				$state = '<span class="center text-success"><i class="fas fa-check" aria-hidden="true"></i></span>';
			else
				$state = '<span class="text-danger"><i class="fas fa-times" aria-hidden="true"></i></span>';
			
			$row[] = $state;
			
			$row[] = $entity->getIpAddress();
			$row[] = $date->doDateTime($request->getLocale(), $entity->getCreatedAt());


			$row[] = "
			 <a href='".$this->generateUrl('LoginHistory_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
}