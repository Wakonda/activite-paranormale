<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\UsefulLink;
use App\Form\Type\UsefulLinkAdminType;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/usefullink')]
class UsefulLinkAdminController extends AdminGenericController
{
	protected $entityName = 'UsefulLink';
	protected $className = UsefulLink::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "UsefulLink_Admin_Index";
	protected $showRoute = "UsefulLink_Admin_Show";
	protected $formName = 'ap_usefullink_usefullinkadmintype';

	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
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
		$em->persist($entityBindded);
		$em->flush();
	}

	#[Route('/', name: 'UsefulLink_Admin_Index')]
    public function index()
    {
		$twig = 'usefullink/UsefulLinkAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'UsefulLink_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'usefullink/UsefulLinkAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'UsefulLink_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = UsefulLinkAdminType::class;
		$entity = new UsefulLink();

		$twig = 'usefullink/UsefulLinkAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'UsefulLink_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = UsefulLinkAdminType::class;
		$entity = new UsefulLink();

		$twig = 'usefullink/UsefulLinkAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'UsefulLink_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = UsefulLinkAdminType::class;

		$twig = 'usefullink/UsefulLinkAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => (!empty($l = $entity->getLanguage()) ? $l->getAbbreviation() : null)]);
    }

	#[Route('/{id}/update', name: 'UsefulLink_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = UsefulLinkAdminType::class;

		$twig = 'usefullink/UsefulLinkAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'UsefulLink_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'UsefulLink_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $request->query->all());
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $request->query->all(), true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

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

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/generate_link_store', name: 'UsefulLink_Admin_GenerateLinkStore')]
	public function generateStoreLink(Request $request)
	{
		$form = $this->createForm(\App\Form\Type\GenerateLinkStoreAdminType::class);
		$partnerId = \App\Entity\Stores\Store::partnerId;
		
		return $this->render('usefullink/UsefulLinkAdmin/generateLinkStore.html.twig', ['form' => $form->createView(), "partnerId" => $partnerId]);
	}

	#[Route('/reload_theme_by_language', name: 'UsefulLink_Admin_ReloadThemeByLanguage')]
	public function reloadThemeByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];

		if(!empty($language))
		{
			$currentLanguagesWebsite = explode(",", $_ENV["LANGUAGES"]);
			if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite))
				$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => 'en']);

			$licences = $em->getRepository(Licence::class)->findByLanguage($language, ['title' => 'ASC']);
		}
		else
			$licences = $em->getRepository(Licence::class)->findAll();

		$licenceArray = [];

		foreach($licences as $licence)
			$licenceArray[] = ["id" => $licence->getId(), "title" => $licence->getTitle()];

		$translateArray['licence'] = $licenceArray;

		return new JsonResponse($translateArray);
	}

	#[Route('/showImageSelectorColorbox', name: 'UsefulLink_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('UsefulLink_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'UsefulLink_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}
}