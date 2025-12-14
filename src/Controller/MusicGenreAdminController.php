<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\MusicGenre;
use App\Entity\FileManagement;
use App\Entity\Language;
use App\Form\Type\MusicGenreAdminType;
use App\Service\ConstraintControllerValidator;

#[Route('/admin/musicgenre')]
class MusicGenreAdminController extends AdminGenericController
{
	protected $entityName = 'MusicGenre';
	protected $className = MusicGenre::class;

	protected $countEntities = "countAdmin"; 
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";

	protected $indexRoute = "MusicGenre_Admin_Index"; 
	protected $showRoute = "MusicGenre_Admin_Show";
	protected $formName = 'ap_music_musicgenreadmintype';
	
	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$ccv->fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);

		// Check for Doublons
		$searchForDoublons = $em->getRepository(MusicGenre::class)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/', name: 'MusicGenre_Admin_Index')]
    public function index()
    {
		$twig = 'music/MusicGenreAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'MusicGenre_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$twig = 'music/MusicGenreAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

	#[Route('/new', name: 'MusicGenre_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = MusicGenreAdminType::class;
		$entity = new MusicGenre();

		$twig = 'music/MusicGenreAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'MusicGenre_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = MusicGenreAdminType::class;
		$entity = new MusicGenre();

		$twig = 'music/MusicGenreAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'MusicGenre_Admin_Edit')]
    public function edit(EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(MusicGenre::class)->find($id);
		$formType = MusicGenreAdminType::class;

		$twig = 'music/MusicGenreAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'MusicGenre_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = MusicGenreAdminType::class;
		$twig = 'music/MusicGenreAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'MusicGenre_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'MusicGenre_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			<a href='".$this->generateUrl('MusicGenre_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			<a href='".$this->generateUrl('MusicGenre_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/showImageSelectorColorbox', name: 'MusicGenre_Admin_ShowImageSelectorColorbox')]
	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('MusicGenre_Admin_LoadImageSelectorColorbox');
	}

	#[Route('/loadImageSelectorColorbox', name: 'MusicGenre_Admin_LoadImageSelectorColorbox')]
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

	#[Route('/internationalization/{id}', name: 'MusicGenre_Admin_Internationalization')]
    public function internationalization(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = MusicGenreAdminType::class;
		$entity = new MusicGenre();

		$entityToCopy = $em->getRepository(MusicGenre::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setTitle($entityToCopy->getTitle());
		$entity->setWikidata($entityToCopy->getWikidata());

		if(!empty($wikicode = $entityToCopy->getWikidata())) {
			$wikidata = new \App\Service\Wikidata($em);
			$data = $wikidata->getTitleAndUrl($wikicode, $language->getAbbreviation());
			
			if(!empty($data) and !empty($data["url"]))
			{
				$sourceArray = [[
					"author" => null,
					"url" => $data["url"],
					"type" => "url",
				]];
				
				$entity->setSource(json_encode($sourceArray));
				
				if(!empty($title = $data["title"]))
					$entity->setTitle($title);
			}
		}
		
		if(!empty($ci = $entityToCopy->getIllustration())) {
			$illustration = new FileManagement();
			$illustration->setTitleFile($ci->getTitleFile());
			$illustration->setRealNameFile($ci->getRealNameFile());
			$illustration->setCaption($ci->getCaption());
			$illustration->setLicense($ci->getLicense());
			$illustration->setAuthor($ci->getAuthor());
			$illustration->setUrlSource($ci->getUrlSource());
			
			$entity->setIllustration($illustration);
		}

		$request->setLocale($language->getAbbreviation());

		$twig = 'music/MusicGenreAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit', "locale" => $language->getAbbreviation()]);
    }
}