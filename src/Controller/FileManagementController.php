<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Testimony;
use App\Entity\FileManagement;
use App\Entity\TestimonyFileManagement;
use App\Form\Type\FileManagementType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class FileManagementController extends AbstractController
{
    private function getNewEntity($em, $className, $idClassName)
	{
		switch($className)
		{
			case "Testimony":
				$entity = new TestimonyFileManagement();
				$entity->setTestimony($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = TestimonyFileManagement::class;
				break;
		}
		return [$entity, $className];
	}

    public function indexAction(EntityManagerInterface $em, $idClassName, $className)
    {
		list($entity, $classNameFileManagement) = $this->getNewEntity($em, $className, $idClassName);
		
		$entities = $em->getRepository($classNameFileManagement)->getAllFilesForTestimonyByIdClassName($idClassName, "file");
		
		$form = $this->createForm(FileManagementType::class, $entity);

        return $this->render('filemanagement/FileManagement/index.html.twig', array(
			'entity' => $entity,
			'form' => $form->createView(),
			'entities' =>$entities,
			'idClassName' =>$idClassName,
			'className' =>$className
        ));
    }
	
	public function createAction(Request $request, EntityManagerInterface $em, $idClassName, $className)
	{
		list($entity, $classNameFileManagement) = $this->getNewEntity($em, $className, $idClassName);
		
		$form = $this->createForm(FileManagementType::class, $entity);
		$kind = "file";

		$form->handleRequest($request);

		if($form->isValid())
		{
			if($request->isXmlHttpRequest())
			{
				$entity->setExtensionFile(substr(strrchr($entity->getRealNameFile(),'.'),1));
				$entity->setKindFile($kind);
				$em->persist($entity);
				$em->flush();

				list($entity,) = $this->getNewEntity($em, $className, $idClassName);
				$form = $this->createForm(FileManagementType::class, $entity);
			}
		}
		
		$entities = $em->getRepository($classNameFileManagement)->getAllFilesForTestimonyByIdClassName($idClassName, $kind);

		return $this->render('filemanagement/FileManagement/index.html.twig', [
			'entity' => $entity,
			'form' => $form->createView(),
			'entities' =>$entities,
			'idClassName' =>$idClassName,
			'className' =>$className
		]);
	}
	
    public function deleteAction(Request $request, EntityManagerInterface $em)
    {
		$id = $request->get('selectedId');

		if($request->isXmlHttpRequest() )
	    {
            $entity = $em->getRepository(FileManagement::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find FileManagement entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return new Response();
    }
	
    public function uploadFileAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $idClassName, $className)
    {
		$newNameFile = "";
		$file = $request->files->all();
		$authorizedExtensionsArray = array('jpg', 'png', 'pdf', 'doc', 'jpeg', 'mp4', 'mp3', 'flv', 'ogg', 'gif', 'wmv', 'ppt', 'pptx', 'docx');

		if($file['uploadFile'] == null)
			$errorMsg = $translator->trans('file.uploadFile.NoFileSelected', [], 'validators');
		elseif($file['uploadFile']->getSize() > $file['uploadFile']->getMaxFilesize())
			$errorMsg = $translator->trans('file.uploadFile.FileTooLarge', [], 'validators');
		elseif(!in_array($file['uploadFile']->guessExtension(), $authorizedExtensionsArray))
			$errorMsg = $translator->trans('file.uploadFile.UnauthorizedExtension', [], 'validators');
		else
		{
			list($entity,) = $this->getNewEntity($em, $className, $idClassName);
			
			$newNameFile = uniqid().'_'.$file['uploadFile']->getClientOriginalName();
			
			$file['uploadFile']->move($entity->getFileManagementPath(), $newNameFile);
			$errorMsg = "OK";
		}
		return new Response("
		<script>window.top.window.uploadEnd('".$errorMsg."', '".$newNameFile."');</script>");
    }

    public function showImageAction(EntityManagerInterface $em, $idClassName, $className)
    {
		list($entity, $classNameFileManagement) = $this->getNewEntity($em, $className, $idClassName);
		$entities = $em->getRepository($classNameFileManagement)->getAllFilesForTestimonyByIdClassName($idClassName);

        return $this->render('filemanagement/FileManagement/showImage.html.twig', [
			'entities' => $entities,
			'idClassName' => $idClassName,
			'className' => $className,
			'mainEntity' => $em->getRepository($entity->getMainEntityClassName())->find($idClassName)
        ]);
    }
	
    public function uploadFileDropzoneAction(Request $request, EntityManagerInterface $em, $idClassName, $className)
    {
		$newNameFile = "";
		$file = $request->files->all();
		$title = $request->request->all()["title"];

		list($entity, $className) = $this->getNewEntity($em, $className, $idClassName);

		$newNameFile = uniqid().'_'.$file['file']->getClientOriginalName();

		$entity->setTitleFile(empty($title) ? $newNameFile : $title);
		$entity->setRealNameFile($newNameFile);
		$entity->setExtensionFile(substr(strrchr($entity->getRealNameFile(),'.'),1));
		$entity->setKindFile("file");
		$em->persist($entity);
		$em->flush();
		
		$file['file']->move($entity->getFileManagementPath(), $newNameFile);

		return new Response();
    }

    public function drawingPaintAction(EntityManagerInterface $em, $idClassName, $className)
    {
		list($entity, $classNameFileManagement) = $this->getNewEntity($em, $className, $idClassName);
		$entities = $em->getRepository($classNameFileManagement)->getAllFilesForTestimonyByIdClassName($idClassName, "drawing");

        return $this->render('filemanagement/FileManagement/drawingPaint.html.twig', [
			'entities' =>$entities,
			'idClassName' =>$idClassName,
			'className' =>$className
        ]);
    }
	
    public function downloadAction(EntityManagerInterface $em, $id, $path, $folder)
    {
		$entity = $em->getRepository(FileManagement::class)->find($id);

		$pathFile = realpath($this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR."$folder/".urldecode($path).$entity->getRealNameFile());

        return $this->file($pathFile, $entity->getRealNameFile(), \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_INLINE);
    }

	// WPAINT
	public function saveImagePaintAction(Request $request, EntityManagerInterface $em, $idClassName, $className)
	{
		$testimony = $em->getRepository(Testimony::class)->find($idClassName);

		list($entity, $classNameFileManagement) = $this->getNewEntity($em, $className, $idClassName);

		$image_base64 = $request->request->get('image');
		$image_base64_array = explode(",", $image_base64);

		$realNameFile = uniqid()."draw.png";
		file_put_contents($testimony->getAssetImagePath().$realNameFile, base64_decode($image_base64_array[1]));
		
		$kind = "drawing";
		$entity->setKindFile($kind);
		$entity->setExtensionFile("png");
		$entity->setRealNameFile($realNameFile);
		$entity->setTitleFile($realNameFile);
		
		$em->persist($entity);
		$em->flush();
		
		$entities = $em->getRepository($classNameFileManagement)->getAllFilesForTestimonyByIdClassName($idClassName, $kind);
		
		return $this->render('filemanagement/FileManagement/drawingPaint.html.twig', array(
			'entity' => $entity,
			'entities' => $entities,
			'idClassName' => $idClassName,
			'className' => $className
		));
	}
}