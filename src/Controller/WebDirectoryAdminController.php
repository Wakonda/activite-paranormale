<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\WebDirectory;
use App\Entity\Language;
use App\Entity\Licence;
use App\Form\Type\WebDirectoryAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * WebDirectoryAdmin controller.
 *
 */
class WebDirectoryAdminController extends AdminGenericController
{
	protected $entityName = 'WebDirectory';
	protected $className = WebDirectory::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "WebDirectory_Admin_Index"; 
	protected $showRoute = "WebDirectory_Admin_Show";
	protected $formName = 'ap_webdirectory_webdirectoryadmintype';

	protected $illustrations = [["field" => "logo", 'selectorFile' => 'photo_selector']];
	
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
		$twig = 'webdirectory/WebDirectoryAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'webdirectory/WebDirectoryAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request)
    {
		$formType = WebDirectoryAdminType::class;
		$entity = new WebDirectory();

		$twig = 'webdirectory/WebDirectoryAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = WebDirectoryAdminType::class;
		$entity = new WebDirectory();

		$twig = 'webdirectory/WebDirectoryAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction($id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = WebDirectoryAdminType::class;

		$twig = 'webdirectory/WebDirectoryAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = WebDirectoryAdminType::class;
		$twig = 'webdirectory/WebDirectoryAdmin/edit.html.twig';

		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
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
			$row[] = '<a href="'.$entity->getLink().'">'.$entity->getLink().'</a>';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getAssetImagePath().$entity->getLogo().'" alt="" width="100px">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('WebDirectory_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('WebDirectory_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function reloadListsByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();

		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = array();
		
		if(!empty($language))
			$licences = $em->getRepository(Licence::class)->findByLanguage($language, array('title' => 'ASC'));
		else
			$licences = $em->getRepository(Licence::class)->findAll();

		$licenceArray = array();

		foreach($licences as $licence)
		{
			$licenceArray[] = array("id" => $licence->getId(), "title" => $licence->getTitle());
		}
		$translateArray['licence'] = $licenceArray;

		return new JsonResponse($translateArray);
	}
	
    public function internationalizationAction(Request $request, $id)
    {
		$formType = WebDirectoryAdminType::class;
		$entity = new WebDirectory();
		
		$em = $this->getDoctrine()->getManager();
		$entityToCopy = $em->getRepository(WebDirectory::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		
		$websiteLanguage = null;
		
		if(!empty($entityToCopy->getWebsiteLanguage()))
			$websiteLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $entityToCopy->getWebsiteLanguage()->getAbbreviation()]);
		
		$licence = null;
		
		if(!empty($entityToCopy->getLicence()))
			$licence = $em->getRepository(Licence::class)->findOneBy(["internationalName" => $entityToCopy->getLicence()->getInternationalName(), "language" => $language]);

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setLink($entityToCopy->getLink());
		$entity->setLanguage($language);
		$entity->setLogo($entityToCopy->getLogo());
		$entity->setLicence($licence);
		$entity->setWebsiteLanguage($websiteLanguage);
		$entity->setSocialNetwork(($entityToCopy->getSocialNetwork()));
		$entity->setFoundedYear($entityToCopy->getFoundedYear());
		$entity->setDefunctYear($entityToCopy->getDefunctYear());
		$entity->setWikidata($entityToCopy->getWikidata());

		$twig = 'webdirectory/WebDirectoryAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }

	public function showImageSelectorColorboxAction()
	{
		return $this->showImageSelectorColorboxGenericAction('WebDirectory_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorboxAction(Request $request)
	{
		return $this->loadImageSelectorColorboxGenericAction($request);
	}
}