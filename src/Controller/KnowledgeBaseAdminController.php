<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\UsefulLink;
use App\Form\Type\KnowledgeBaseAdminType;
use App\Service\ConstraintControllerValidator;

class KnowledgeBaseAdminController extends AdminGenericController
{
	protected $entityName = 'UsefulLink';
	protected $className = UsefulLink::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "KnowledgeBase_Admin_Index"; 
	protected $showRoute = "KnowledgeBase_Admin_Show";
	protected $formName = 'ap_knowledgebase_knowledgebaseadmintype';

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
		$entityBindded->setCategory(UsefulLink::RESOURCE_FAMILY);

		$dom = new \DOMDocument();
		$dom->loadHTML(mb_convert_encoding($entityBindded->getText(), 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD);

		foreach($dom->getElementsByTagName('pre') as $pre) {
			$classes = explode(" ", $pre->getAttribute("class"));
			$classes[] = "line-numbers";
			$classes = array_unique($classes);
			$pre->setAttribute("class", implode(" ", $classes));
		}

		$html = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $dom->saveHTML());

		$entityBindded->setText($html);
		$em->persist($entityBindded);
		$em->flush();
	}

    public function indexAction()
    {
		$twig = 'usefullink/KnowledgeBaseAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'usefullink/KnowledgeBaseAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = KnowledgeBaseAdminType::class;
		$entity = new UsefulLink();

		$twig = 'usefullink/KnowledgeBaseAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = KnowledgeBaseAdminType::class;
		$entity = new UsefulLink();

		$twig = 'usefullink/KnowledgeBaseAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

    public function editAction(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = KnowledgeBaseAdminType::class;

		$twig = 'usefullink/KnowledgeBaseAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => (!empty($l = $entity->getLanguage()) ? $l->getAbbreviation() : null)]);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = KnowledgeBaseAdminType::class;

		$twig = 'usefullink/KnowledgeBaseAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, ["category_filter" => UsefulLink::RESOURCE_FAMILY]);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, ["category_filter" => UsefulLink::RESOURCE_FAMILY], true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('KnowledgeBase_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('KnowledgeBase_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function generateStoreLinkAction(Request $request)
	{
		$form = $this->createForm(\App\Form\Type\GenerateLinkStoreAdminType::class);
		$partnerId = \App\Entity\Stores\Store::partnerId;
		
		return $this->render('usefullink/KnowledgeBaseAdmin/generateLinkStore.html.twig', ['form' => $form->createView(), "partnerId" => $partnerId]);
	}
}