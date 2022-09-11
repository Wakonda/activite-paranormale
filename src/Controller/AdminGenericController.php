<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Contracts\Translation\TranslatorInterface;

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

	abstract public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal);
	abstract public function postValidationAction($form, $entityBindded);

	public function __construct(APParseHTML $parser) {
		$this->parser = $parser;
	}

	protected function defaultValueForMappedSuperclassBase(Request $request, $entity)
	{
		if((is_subclass_of($entity, "App\Entity\MappedSuperclassBase") || method_exists($entity, "setLanguage")) and empty($entity->getLanguage()))
		{
			$em = $this->getDoctrine()->getManager();
			$language = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $request->getLocale()));
			$entity->setLanguage($language);
		}

		if(is_subclass_of($entity, "App\Entity\MappedSuperclassBase") or method_exists($entity, "getState"))
		{
			if(!empty($language))
			{
				$state = $em->getRepository(State::class)->findOneBy(array("language" => $language, "internationalName" => "Validate"));
				if(!empty($state))	
					$entity->setState($state);
			}
		}
	}
	
	protected function datatablesParameters(Request $request): array {
		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');

		$sortByColumn = array();
		$sortDirColumn = array();
			
		for($i=0 ; $i<intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}

		$searchByColumns = array();
		$iColumns = $request->query->get('iColumns');

		for($i=0; $i < $iColumns; $i++)
			$searchByColumns[] = $request->query->get('sSearch_'.$i);
		
		return [$iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns];
	}
	
	public function indexDatatablesGenericAction($request)
	{
		$em = $this->getDoctrine()->getManager();

		list($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns) = $this->datatablesParameters($request);

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => array()
		);
		
		return array('entities' => $entities, 'output' => $output);
	}
	
    public function indexGenericAction($twig)
    {
        return $this->render($twig);
    }

    /**
     * Finds and displays a entity.
     *
     */
    public function showGenericAction($id, $twig, $optionsRender = [])
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->className)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render($twig, array_merge($optionsRender, array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        )));
    }
	
    /**
     * Displays a form to create a new entity.
     *
     */
    public function newGenericAction(Request $request, $twig, $entity, $formType, $options = [])
    {
		$this->defaultValueForMappedSuperclassBase($request, $entity);
        $form = $this->createForm($formType, $entity, $options);

        return $this->render($twig, array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new entity.
     *
     */
    public function createGenericAction(Request $request, ConstraintControllerValidator $ccv, $translator, $twig, $entity, $formType, $options = [])
    {
        $form = $this->createForm($formType, $entity, $options);

        $form->handleRequest($request);

		$this->validationForm($request, $ccv, $translator, $form, $entity, $entity);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
			
			$this->uploadFile($entity, $form);

            $em->persist($entity);
            $em->flush();

			$this->postValidationAction($form, $entity);

            return $this->redirect($this->generateUrl($this->showRoute, array('id' => $entity->getId())));
        }

        return $this->render($twig, array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing entity.
     *
     */
    public function editGenericAction($id, $twig, $formType, $options = [])
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->className)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }

        $editForm = $this->createForm($formType, $entity, $options);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render($twig, array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing entity.
     *
     */
    public function updateGenericAction($request, ConstraintControllerValidator $ccv, $translator, $id, $twig, $formType, $options = [])
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->className)->find($id);
		$entityOriginal = clone $entity;

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }

        $editForm   = $this->createForm($formType, $entity, $options);
        $deleteForm = $this->createDeleteForm($id);

		$editForm->handleRequest($request);
		$this->validationForm($request, $ccv, $translator, $editForm, $entity, $entityOriginal);

        if ($editForm->isValid()) {
			$this->uploadFile($entity, $editForm, $entityOriginal);
            $em->persist($entity);
            $em->flush();
			
			$this->postValidationAction($editForm, $entity);

            return $this->redirect($this->generateUrl($this->showRoute, array('id' => $id)));
        }

        return $this->render($twig, array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a entity.
     *
     */
    public function deleteGenericAction($id)
    {
		$em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->className)->find($id);

		$em->remove($entity);

		$em->flush();

        return $this->redirect($this->generateUrl($this->indexRoute));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', HiddenType::class)
            ->getForm()
        ;
    }

    public function WYSIWYGUploadFileGenericAction(Request $request, APImgSize $imgSize, $entity)
    {
		$file = $request->files->get('image');

		$mimeTypeAvailable = array('image/png', 'image/jpg', 'image/gif', 'image/jpeg', 'image/pjpeg');
		
		if(in_array($file->getMimeType(), $mimeTypeAvailable))
		{
			$newNameFile = uniqid().'-'.$file->getClientOriginalName();
			$file->move($entity->getAssetImagePath(), $newNameFile);
			
			$filelink = '/'.$entity->getAssetImagePath().$newNameFile;
			$img = $imgSize->adaptImageSize(550, $entity->getAssetImagePath().$newNameFile);

			return new Response("<script>
			top.$('.mce-btn.mce-open').parent().find('.mce-textbox').val('".$filelink."');
			top.$('.mce-last.mce-formitem').parent().find('input[aria-label=\'Width\']').val('".$img[0]."');
			</script>");
		}
		return new Response("<script>alert('Error');</script>");
    }

    public function showImageSelectorColorboxGenericAction($urlAjax)
    {
		return $this->render('index/Form/showImageSelectorColorbox.html.twig', array('url_ajax' => $urlAjax));
    }

    public function loadImageSelectorColorboxGenericAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$current_page = $request->request->get('page');
		$search = $request->request->get('search');
		
		$number_photo_by_page = 8;
		$start = ($current_page - 1) * $number_photo_by_page;
		$number_photo = $em->getRepository($this->className)->getFileSelectorColorboxAdmin($start, $number_photo_by_page, $search, true);
		$number_pages = ceil($number_photo / $number_photo_by_page);

		$entities = $em->getRepository($this->className)->getFileSelectorColorboxAdmin($start, $number_photo_by_page, $search);
		
		return $this->render('index/Form/showImageSelectorColorboxResult.html.twig', array('entities' => $entities, 'last_page' => $number_pages, 'current_page' => $current_page));
    }

	protected function getLanguageByDefault($request, $formName)
	{
		$valueForm = $request->request->get($formName);
		$language = $this->getDoctrine()->getManager()->getRepository(Language::class)->find($valueForm['language']);

		if(empty($language))
			$abbreviation = $request->getLocale();
		else
			$abbreviation = $language->getAbbreviation();

		return $abbreviation;
	}
	
	private function uploadFile($entity, $form, $entityOriginal = null)
	{
		$regex = '/[^a-zA-Z0-9_-]+/';
// return;
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
					
					file_put_contents($entity->getTmpUploadRootDir().$filename, $html);

					$existingFile = $filename;
				}

				if(!empty($existingFile)) {
					$entity->$getter()->setTitleFile($existingFile);
				} elseif(!empty($existingFile = $entityOriginal->getIllustration()->getTitleFile())) {
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
				$entity->$getter()->getTitleFile()->move($entity->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($entity->$getter()->getTitleFile()))
					$entity->$getter()->getTitleFile()->move($entity->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($entity->$getter()->getTitleFile()))
				$entity->$getter()->setTitleFile($NewNameFile);
		}
	}
	
	public function archiveGenericArchive($id, $additionalFiles = [])
	{
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->className)->find($id);
		$entity->setArchive(!$entity->getArchive());
		
		if($entity->getArchive()) {
			$this->moveFiles("public", "private", $entity, $additionalFiles);
		} else {
			$this->moveFiles("private", "public", $entity, $additionalFiles);
		}
		$em->persist($entity);
		$em->flush();

		return $this->redirect($this->generateUrl($this->showRoute, array('id' => $id)));
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
	
	protected function saveNewBiographies(&$entityBindded, $form, string $field, bool $isFormChild = true)
	{
		$em = $this->getDoctrine()->getManager();

		if(!$isFormChild) {
			foreach ($form->getData()->getAuthors() as $newBiography)
			{
				if(empty($newBiography->getId())) {
					$generator = new \Ausi\SlugGenerator\SlugGenerator;
					$newBiography->setInternationalName($generator->generate($newBiography->getTitle()).uniqid());
					$newBiography->setLanguage($em->getRepository(Language::class)->find($entityBindded->getLanguage()->getId()));
					$newBiography->setKind(Biography::PERSON);

					$em->persist($newBiography);
				}
			}
			
		} else {
			foreach ($form->get($field) as $formChild)
			{
				$internationalName = $formChild->get('internationalName')->getData();
				$newBiography = $formChild->getData();
				dump($newBiography);
				if($internationalName == "+") {
					$generator = new \Ausi\SlugGenerator\SlugGenerator;
					$newBiography->getBiography()->setInternationalName($generator->generate($newBiography->getBiography()->getTitle()).uniqid());
					$newBiography->getBiography()->setLanguage($em->getRepository(Language::class)->find($entityBindded->getLanguage()->getId()));
					$newBiography->getBiography()->setKind(Biography::PERSON);
					
					$em->persist($newBiography->getBiography());
				} else {
					$biography = $em->getRepository(Biography::class)->findOneBy(["internationalName" => $internationalName, "language" => $entityBindded->getLanguage()]);
					
					if(empty($biography)) {
						$b = new Biography();
						$b->setInternationalName($internationalName);
						$b->setLanguage($em->getRepository(Language::class)->find($entityBindded->getLanguage()->getId()));
						$b->setKind(Biography::PERSON);
						$b->setTitle($newBiography->getBiography()->getTitle());
						$b->setBirthDate($newBiography->getBiography()->getBirthDate());
						$b->setDeathDate($newBiography->getBiography()->getDeathDate());
						$b->setLinks($newBiography->getBiography()->getLinks());		$country = null;
			
						if(!empty($n = $newBiography->getBiography()->getNationality())) {
							$country = $em->getRepository(Country::class)->findOneBy(["internationalName" => $n->getInternationalName(), "language" => $entityBindded->getLanguage()]);
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
}