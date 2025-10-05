<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Publisher;
use App\Entity\Language;
use App\Entity\FileManagement;
use App\Form\Type\PublisherAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Publisher controller.
 *
 */
class PublisherAdminController extends AdminGenericController
{
	protected $entityName = 'Publisher';
	protected $className = Publisher::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Publisher_Admin_Index"; 
	protected $showRoute = "Publisher_Admin_Show";

	protected $illustrations = [["field" => "illustration", "selectorFile" => "photo_selector"]];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);

		if($searchForDoublons > 0)
			$form->get('title')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'book/PublisherAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }
	
    public function showAction(EntityManagerInterface $em, $id)
    {
		$twig = 'book/PublisherAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig);
    }

    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = PublisherAdminType::class;
		$entity = new Publisher();

		$twig = 'book/PublisherAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType);
    }
	
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = PublisherAdminType::class;
		$entity = new Publisher();

		$twig = 'book/PublisherAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType);
    }
	
    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = PublisherAdminType::class;

		$twig = 'book/PublisherAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType);
    }
	
	public function updateAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = PublisherAdminType::class;
		$twig = 'book/PublisherAdmin/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType);
    }
	
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	public function indexDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = "
			 <a href='".$this->generateUrl('Publisher_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Publisher_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function showImageSelectorColorbox()
	{
		return $this->showImageSelectorColorboxGeneric('Publisher_Admin_LoadImageSelectorColorbox');
	}
	
	public function loadImageSelectorColorbox(Request $request, EntityManagerInterface $em)
	{
		return $this->loadImageSelectorColorboxGeneric($request, $em);
	}

    public function internationalization(Request $request, EntityManagerInterface $em, $id)
    {
		$formType = PublisherAdminType::class;
		$entity = new Publisher();

		$entityToCopy = $em->getRepository(Publisher::class)->find($id);
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));

		$entity->setTitle($entityToCopy->getTitle());
		$entity->setWebsite($entityToCopy->getWebsite());
		$entity->setInternationalName($entityToCopy->getInternationalName());
		$entity->setLanguage($language);
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

		$twig = 'book/PublisherAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'edit']);
    }
}