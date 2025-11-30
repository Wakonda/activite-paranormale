<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use Doctrine\ORM\Query\ResultSetMapping;

use App\Entity\Page;
use App\Entity\Language;
use App\Form\Type\PageAdminType;
use App\Service\ConstraintControllerValidator;
use App\Service\APImgSize;

/**
 * Page controller.
 *
 */
class PageAdminController extends AdminGenericController
{
	protected $entityName = 'Page';
	protected $className = Page::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Page_Admin_Index"; 
	protected $showRoute = "Page_Admin_Show";
	protected $formName = 'ap_page_pageadmintype';

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'page/PageAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'page/PageAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = PageAdminType::class;
		$entity = new Page();

		$twig = 'page/PageAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = PageAdminType::class;
		$entity = new Page();

		$twig = 'page/PageAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Page::class)->find($id);
		$formType = PageAdminType::class;

		$twig = 'page/PageAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = PageAdminType::class;
		
		$twig = 'page/PageAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

    public function WYSIWYGUploadFile(Request $request, APImgSize $imgSize)
    {
		return $this->WYSIWYGUploadFileGeneric($request, $imgSize, new Page());
    }
	
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = $entity->getInternationalName();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Page_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Page_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
	
    public function internationalizationAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = PageAdminType::class;
		$entity = new Page();

		$entityToCopy = $em->getRepository(Page::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setLanguage($language);

		$request->setLocale($language->getAbbreviation());

		$twig = 'page/PageAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }
}