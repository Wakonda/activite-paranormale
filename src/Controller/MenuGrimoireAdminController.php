<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\MenuGrimoire;
use App\Entity\Language;
use App\Form\Type\MenuGrimoireAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * MenuGrimoire controller.
 *
 */
class MenuGrimoireAdminController extends AdminGenericController
{
	protected $entityName = 'MenuGrimoire';
	protected $className = MenuGrimoire::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "MenuGrimoire_Admin_Index"; 
	protected $showRoute = "MenuGrimoire_Admin_Show";
	protected $illustrations = [["field" => "photo"]];

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

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
		$twig = 'witchcraft/MenuGrimoireAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'witchcraft/MenuGrimoireAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = MenuGrimoireAdminType::class;
		$entity = new MenuGrimoire();

		$twig = 'witchcraft/MenuGrimoireAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = MenuGrimoireAdminType::class;
		$entity = new MenuGrimoire();

		$twig = 'witchcraft/MenuGrimoireAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction($id)
    {
		$formType = MenuGrimoireAdminType::class;

		$twig = 'witchcraft/MenuGrimoireAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = MenuGrimoireAdminType::class;
		$twig = 'witchcraft/MenuGrimoireAdmin/edit.html.twig';

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
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getAssetImagePath().$entity->getPhoto().'" alt="" width="100px">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('MenuGrimoire_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('MenuGrimoire_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	protected function defaultValueForMappedSuperclassBase(Request $request, $entity)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $request->getLocale()));
		$entity->setLanguage($language);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = MenuGrimoireAdminType::class;
		$entity = new MenuGrimoire();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(MenuGrimoire::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'witchcraft/MenuGrimoireAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit']);
    }
}