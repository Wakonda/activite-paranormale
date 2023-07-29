<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
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
	protected $illustrations = [["field" => "photo", 'selectorFile' => 'photo_selector']];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidationAction($form, EntityManagerInterface $em, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/index.html.twig';//rCFebs985£
		return $this->indexGenericAction($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/show.html.twig';
		return $this->showGenericAction($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->createGenericAction($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/edit.html.twig';
		return $this->editGenericAction($em, $id, $twig, $formType);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$twig = 'witchcraft/WitchcraftThemeToolAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $em, $ccv, $translator, $id, $twig, $formType);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGenericAction($em, $id);
    }

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('WitchcraftThemeTool_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('WitchcraftThemeTool_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = WitchcraftThemeToolAdminType::class;
		$entity = new WitchcraftThemeTool();

		$entityToCopy = $em->getRepository(WitchcraftThemeTool::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setPhoto($entityToCopy->getPhoto());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'witchcraft/WitchcraftThemeToolAdmin/new.html.twig';
		return $this->newGenericAction($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }
}