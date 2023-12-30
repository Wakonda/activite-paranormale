<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Language;
use App\Entity\State;
use App\Entity\FileManagement;
use App\Entity\Biography;
use App\Service\APImgSize;
use App\Service\ConstraintControllerValidator;
use App\Service\APParseHTML;
use App\Form\Type\InternationalizationAdminType;

abstract class AdminGenericController extends AbstractController
{
	protected $entityName = "";
	protected $className = "";

	protected $countEntities = ""; 
	
	protected $indexRoute = ""; 
	protected $showRoute = "";
	
	protected $illustrations = [];
	
	private $parser;

	abstract public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal);
	abstract public function postValidationAction($form, EntityManagerInterface $em, $entityBindded);

	public function __construct(APParseHTML $parser) {
		$this->parser = $parser;
	}

	protected function defaultValueForMappedSuperclassBase(Request $request, EntityManagerInterface $em, $entity)
	{
		if((is_subclass_of($entity, "App\Entity\MappedSuperclassBase") || method_exists($entity, "setLanguage")) and empty($entity->getLanguage()))
		{
			$language = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $request->getLocale()]);
			$entity->setLanguage($language);
		}

		if(is_subclass_of($entity, "App\Entity\MappedSuperclassBase") or method_exists($entity, "getState"))
		{
			if(!empty($language))
			{
				$state = $em->getRepository(State::class)->findOneBy(["language" => $language, "internationalName" => "Validate"]);
				if(!empty($state))	
					$entity->setState($state);
			}
		}
	}
	
	protected function datatablesParameters(Request $request): array {
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

		$searchByColumns = [];
		$iColumns = $request->query->all('columns');

		foreach($iColumns as $iColumn) {
			$searchByColumns[] = $iColumn["search"];
		}
		
		return [$iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns];
	}
	
	public function indexDatatablesGenericAction($request, EntityManagerInterface $em)
	{
		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];
		
		return ['entities' => $entities, 'output' => $output];
	}
	
    public function indexGenericAction($twig)
    {
        return $this->render($twig);
    }

    /**
     * Finds and displays a entity.
     *
     */
    public function showGenericAction(EntityManagerInterface $em, $id, $twig, $optionsRender = [])
    {
        $entity = $em->getRepository($this->className)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render($twig, array_merge($optionsRender, [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]));
    }
	
    /**
     * Displays a form to create a new entity.
     *
     */
    public function newGenericAction(Request $request, $em, $twig, $entity, $formType, $options = [])
    {
		$this->defaultValueForMappedSuperclassBase($request, $em, $entity);
        $form = $this->createForm($formType, $entity, $options);

        return $this->render($twig, [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    /**
     * Creates a new entity.
     *
     */
    public function createGenericAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, $translator, $twig, $entity, $formType, $options = [])
    {
        $form = $this->createForm($formType, $entity, $options);

        $form->handleRequest($request);

		$this->validationForm($request, $em, $ccv, $translator, $form, $entity, $entity);

        if ($form->isValid()) {
			$this->uploadFile($entity, $form);

            $em->persist($entity);
            $em->flush();

			$this->postValidationAction($form, $em, $entity);

            return $this->redirect($this->generateUrl($this->showRoute, ['id' => $entity->getId()]));
        }

        return $this->render($twig, [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    /**
     * Displays a form to edit an existing entity.
     *
     */
    public function editGenericAction(EntityManagerInterface $em, $id, $twig, $formType, $options = [])
    {
        $entity = $em->getRepository($this->className)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }

        $editForm = $this->createForm($formType, $entity, $options);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render($twig, [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Edits an existing entity.
     *
     */
    public function updateGenericAction($request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, $translator, $id, $twig, $formType, $options = [])
    {
        $entity = $em->getRepository($this->className)->find($id);
		$entityOriginal = clone $entity;

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }

        $editForm   = $this->createForm($formType, $entity, $options);
        $deleteForm = $this->createDeleteForm($id);

		$editForm->handleRequest($request);
		$this->validationForm($request, $em, $ccv, $translator, $editForm, $entity, $entityOriginal);

        if ($editForm->isValid()) {
			$this->uploadFile($entity, $editForm, $entityOriginal);
            $em->persist($entity);
            $em->flush();
			
			$this->postValidationAction($editForm, $em, $entity);

            return $this->redirect($this->generateUrl($this->showRoute, ['id' => $id]));
        }

        return $this->render($twig, [
            'entity' => $entity,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a entity.
     *
     */
    public function deleteGenericAction(EntityManagerInterface $em, $id)
    {
        $entity = $em->getRepository($this->className)->find($id);

		$em->remove($entity);

		$em->flush();

        return $this->redirect($this->generateUrl($this->indexRoute));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(['id' => $id])
            ->add('id', HiddenType::class)
            ->getForm()
        ;
    }

    public function WYSIWYGUploadFileGenericAction(Request $request, APImgSize $imgSize, $entity)
    {
		$file = $request->files->get('image');

		$mimeTypeAvailable = ['image/png', 'image/jpg', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/webp'];
		
		if(in_array($file->getClientMimeType(), $mimeTypeAvailable))
		{
			$newNameFile = uniqid().'-'.$file->getClientOriginalName();
			$file->move($entity->getAssetImagePath(), $newNameFile);
			
			$filelink = '/'.$entity->getAssetImagePath().$newNameFile;
			$img = $imgSize->adaptImageSize(550, $entity->getAssetImagePath().$newNameFile);

			return new JsonResponse(["title" => $filelink]);
		}
		return new JsonResponse(["title" => "Error"]);
    }

    public function showImageSelectorColorboxGenericAction($urlAjax)
    {
		return $this->render('index/Form/showImageSelectorColorbox.html.twig', ['url_ajax' => $urlAjax]);
    }

    public function loadImageSelectorColorboxGenericAction(Request $request, EntityManagerInterface $em)
    {
		$current_page = $request->request->get('page');
		$search = $request->request->get('search');
		
		$number_photo_by_page = 8;
		$start = ($current_page - 1) * $number_photo_by_page;
		$number_photo = $em->getRepository($this->className)->getFileSelectorColorboxAdmin($start, $number_photo_by_page, $search, true);
		$number_pages = ceil($number_photo / $number_photo_by_page);

		$entities = $em->getRepository($this->className)->getFileSelectorColorboxAdmin($start, $number_photo_by_page, $search);
		
		return $this->render('index/Form/showImageSelectorColorboxResult.html.twig', ['entities' => $entities, 'last_page' => $number_pages, 'current_page' => $current_page]);
    }

	protected function getLanguageByDefault(Request $request, EntityManagerInterface $em, $formName)
	{
		$valueForm = $request->request->all($formName);
		$language = $em->getRepository(Language::class)->find($valueForm['language']);

		if(empty($language))
			$abbreviation = $request->getLocale();
		else
			$abbreviation = $language->getAbbreviation();

		return $abbreviation;
	}
	
	protected function uploadFile($entity, $form, $entityOriginal = null)
	{
		$regex = '/[^a-zA-Z0-9_-]+/';

		foreach($this->illustrations as $illustration)
		{
			$fieldName = $illustration["field"];
			$getter = "get".ucfirst($illustration["field"]);
			$setter = "set".ucfirst($illustration["field"]);

			if(empty($entity->$getter()))
				return;
				
			if(!method_exists($entity->$getter(), "getTitleFile"))
				return;

			if(empty($entity->$getter()->getTitleFile()))
			{
				$existingFile = null;
				if(isset($illustration['selectorFile']) and ($f = $form->get($fieldName)->get($illustration['selectorFile'])->getData()) != null)
					$existingFile = $f;

				if(filter_var($existingFile, FILTER_VALIDATE_URL)) {
					$html = $this->parser->getContentURL(urldecode($existingFile));
					$pi = pathinfo($existingFile);
					$filename = preg_replace($regex, "-", urldecode($pi["filename"])).".".$pi["extension"];
					$filename = uniqid()."_".$filename;

					list($filename, $content) = APImgSize::convertToWebP($html, $filename);
					
					file_put_contents($entity->getTmpUploadRootDir().$filename, $html);

					$existingFile = $filename;
				}

				if(empty($existingFile))
					return;

				if(!empty($existingFile)) {
					$entity->$getter()->setTitleFile($existingFile);
				} elseif(!empty($entityOriginal->getIllustration()) and !empty($existingFile = $entityOriginal->getIllustration()->getTitleFile())) {
					$entity->$getter()->setTitleFile($existingFile);
				}

				$entity->$getter()->setRealNameFile($existingFile);
				$entity->$getter()->setExtensionFile(pathinfo($existingFile, PATHINFO_EXTENSION));

				return;
			}

			$filename = basename($entity->$getter()->getTitleFile()->getClientOriginalName());
			$explodeFilename = explode(".", strrev($filename), 2);
			$NNFile = preg_replace($regex, "-", strrev($explodeFilename[1]));
			$ExtFile = strrev($explodeFilename[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;

			$entity->$getter()->setExtensionFile(pathinfo($NewNameFile, PATHINFO_EXTENSION));
			$entity->$getter()->setKindFile(FileManagement::FILE_KIND);
			$entity->$getter()->setRealNameFile($NewNameFile);

			if(!$entity->$getter()->getId()){
				list($filename, $content) = APImgSize::convertToWebP($entity->$getter()->getTitleFile(), $NewNameFile);

				file_put_contents($entity->getTmpUploadRootDir().$filename, $content);
				
				$entity->$getter()->setExtensionFile(pathinfo($filename, PATHINFO_EXTENSION));
				$entity->$getter()->setKindFile(FileManagement::FILE_KIND);
				$entity->$getter()->setRealNameFile($filename);
				$NewNameFile = $filename;
			} else {
				if (is_object($entity->$getter()->getTitleFile())) {
					$entity->$getter()->getTitleFile()->move($entity->getUploadRootDir(), $NewNameFile);
				}
			}
			if (is_object($entity->$getter()->getTitleFile())) {
				$entity->$getter()->setTitleFile($NewNameFile);
			}
		}
	}
	
	public function archiveGenericArchive(EntityManagerInterface $em, $id, $additionalFiles = [])
	{
        $entity = $em->getRepository($this->className)->find($id);
		$entity->setArchive(!$entity->getArchive());
		
		if($entity->getArchive())
			$this->moveFiles("public", "private", $entity, $additionalFiles);
		else
			$this->moveFiles("private", "public", $entity, $additionalFiles);

		$em->persist($entity);
		$em->flush();

		return $this->redirect($this->generateUrl($this->showRoute, ['id' => $id]));
	}
	
	private function moveFiles($baseFrom, $baseTo, $entity, $additionalFiles = []): void
	{
		$basePath = $this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR;
		
		foreach($this->illustrations as $illustration)
		{
			$fieldName = $illustration["field"];
			$getter = "get".ucfirst($illustration["field"]);
			
			if(method_exists($entity->$getter(), "getTitleFile"))
				$filename = $entity->$getter()->getTitleFile();
			else
				$filename = $entity->$getter();
				
			if(empty($filename))
				continue;

			if(file_exists($basePath.$baseFrom.DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$filename))
				rename($basePath.$baseFrom.DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$filename, $basePath.$baseTo.DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$filename);
		}
		
		foreach($additionalFiles as $filename)
		{
			if(empty($filename))
				continue;

			if(file_exists($basePath.$baseFrom.DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$filename))
				rename($basePath.$baseFrom.DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$filename, $basePath.$baseTo.DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$filename);
		}

		$html = preg_replace('~[[:cntrl:]]~', '', $entity->getText());

		$dom = new \DOMDocument();
		$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		foreach ($dom->getElementsByTagName('img') as $item) {
			if(!empty($item->getAttribute("src"))) {
				$filename = pathinfo($item->getAttribute("src"), PATHINFO_BASENAME);

				if(file_exists(realpath($basePath.$baseFrom.DIRECTORY_SEPARATOR.$item->getAttribute("src"))))
					rename($basePath.$baseFrom.DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$filename, $basePath.$baseTo.DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$filename);
			}
		}
	}
	
	protected function saveNewBiographies(EntityManagerInterface $em, &$entityBindded, $form, string $field)
	{
		foreach ($form->get($field) as $formChild)
		{
			$internationalName = $formChild->get('internationalName')->getData();
			$wikidata = $formChild->get('wikidata')->getData();
			$newBiography = $formChild->getData();

			if($internationalName == "+") {
				$generator = new \Ausi\SlugGenerator\SlugGenerator;
				$newBiography->getBiography()->setInternationalName($generator->generate($newBiography->getBiography()->getTitle()).uniqid());
				$newBiography->getBiography()->setLanguage($em->getRepository(Language::class)->find($entityBindded->getLanguage()->getId()));
				$newBiography->getBiography()->setKind(Biography::PERSON);
				$newBiography->getBiography()->setWikidata($wikidata);

				$em->persist($newBiography->getBiography());
			} else {
				$biography = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $internationalName, "language" => $entityBindded->getLanguage()]);

				if(empty($biography)) {
					$b = new Biography();
					$b->setInternationalName($internationalName);
					$b->setLanguage($em->getRepository(Language::class)->find($entityBindded->getLanguage()->getId()));
					$b->setKind(Biography::PERSON);
					$b->setWikidata($wikidata);
					$b->setTitle($newBiography->getBiography()->getTitle());
					$b->setBirthDate($newBiography->getBiography()->getBirthDate());
					$b->setDeathDate($newBiography->getBiography()->getDeathDate());
					$b->setLinks($newBiography->getBiography()->getLinks());
					
					$country = null;

					if(!empty($n = $newBiography->getBiography()->getNationality())) {
						$country = $em->getRepository(Region::class)->findOneBy(["internationalName" => $n->getInternationalName(), "language" => $entityBindded->getLanguage()]);
						$b->setNationality($country);
					}

					if(!empty($ci = $newBiography->getBiography()->getIllustration())) {
						$illustration = new FileManagement();
						$illustration->setTitleFile($ci->getTitleFile());
						$illustration->setRealNameFile($ci->getRealNameFile());
						$illustration->setCaption($ci->getCaption());
						$illustration->setLicense($ci->getLicense());
						$illustration->setAuthor($ci->getAuthor());
						$illustration->setUrlSource($ci->getUrlSource());
						$illustration->setExtensionFile(pathinfo($ci->getRealNameFile(), PATHINFO_EXTENSION));

						$b->setIllustration($illustration);

						$em->persist($ci);
					}
					$em->persist($b);
					
					$newBiography->setBiography($b);
				} else {
					$newBiography->setBiography($biography);
					$em->persist($newBiography->getBiography());
				}
			}
		}
	}
}