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
	protected $formName = 'ap_usefullink_usefullinkadmintype';

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidationAction($form, $entityBindded)
	{
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
        $em = $this->getDoctrine()->getManager();
		$em->persist($entityBindded);
		$em->flush();
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
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = UsefulLinkAdminType::class;
		$entity = new UsefulLink();

		$twig = 'usefullink/UsefulLinkAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction($id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = UsefulLinkAdminType::class;

		$twig = 'usefullink/UsefulLinkAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = UsefulLinkAdminType::class;

		$twig = 'usefullink/UsefulLinkAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		return $this->deleteGenericAction($id);
    }

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$em = $this->getDoctrine()->getManager();

		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $request->query->all());
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $request->query->all(), true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => array()
		);

		foreach($entities as $entity)
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
			$row[] = !empty($entity->getCategory()) ? $translator->trans('usefullink.admin.'.ucfirst($entity->getCategory()), [], 'validators') : "";
			$row[] = '<ul>'.implode("", $links).'</ul>';
			$row[] = "
			 <a href='".$this->generateUrl('UsefulLink_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('UsefulLink_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>
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

	public function reloadThemeByLanguageAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language))
		{
			$themes = $em->getRepository(Theme::class)->findByLanguage($language, array('title' => 'ASC'));
			
			$currentLanguagesWebsite = array("fr", "en", "es");
			if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite))
				$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => 'en'));

			$states = $em->getRepository(State::class)->findByLanguage($language, array('title' => 'ASC'));
			$licences = $em->getRepository(Licence::class)->findByLanguage($language, array('title' => 'ASC'));
		}
		else
		{
			$themes = $em->getRepository(Theme::class)->findAll();
			$states = $em->getRepository(State::class)->findAll();
			$licences = $em->getRepository(Licence::class)->findAll();
		}
	
		$licenceArray = [];

		foreach($licences as $licence)
		{
			$licenceArray[] = array("id" => $licence->getId(), "title" => $licence->getTitle());
		}
		$translateArray['licence'] = $licenceArray;
		
		$response = new Response(json_encode($translateArray));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
}