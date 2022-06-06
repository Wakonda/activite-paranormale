<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\WitchcraftThemeTool;
use App\Entity\Language;
use App\Form\Type\WitchcraftThemeToolAdminType;
use App\Service\ConstraintControllerValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * WitchcraftThemeToolAdmin controller.
 *
 */
class WitchcraftThemeToolAdminController extends AdminGenericController
{
	protected $entityName = 'WitchcraftThemeTool';
	protected $className = WitchcraftThemeTool::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "WitchcraftThemeTool_Admin_Index"; 
	protected $showRoute = "WitchcraftThemeTool_Admin_Show";
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
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/index.html.twig';//rCFebs985£
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(Request $request, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/edit.html.twig';

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
			 <a href='".$this->generateUrl('WitchcraftThemeTool_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('WitchcraftThemeTool_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(WitchcraftThemeTool::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit']);
    }
}