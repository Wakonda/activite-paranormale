<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\TestimonyFileManagement;
use App\Form\Type\FileManagementEditType;
use App\Service\APImgSize;
use App\Service\ResmushIt;

class FileManagementAdminController extends AbstractController
{
	private $mimeTypes = ['image/png', 'image/jpg', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/webp', "video/webm"];
	
	public function listFilesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize)
	{
		$limit = 9;

		$basePath = $this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR;
		$photoPath = DIRECTORY_SEPARATOR."extended".DIRECTORY_SEPARATOR."photo";

		$folderPublic = realpath($basePath."public".$photoPath);
		$folderPrivate = realpath($basePath."private".$photoPath );

		$listFolders = array_unique(array_merge($this->listFolders($folderPublic, $folderPublic), $this->listFolders($folderPrivate, $folderPrivate)));

		$selectedFolder = $request->query->get("folder", "news");
		$page = $request->query->get("page", 1);
		$sort = $request->query->get("sort", "sortByNameAsc");
		$mimeType = $request->query->get("mime", null);
		
		$offset = ($page - 1) * $limit;

		$this->getDirContents($folderPublic.DIRECTORY_SEPARATOR.$selectedFolder, $filelist, $mimeType);
		$this->getDirContents($folderPrivate.DIRECTORY_SEPARATOR.$selectedFolder, $filelist, $mimeType);
		// die;
		$filelist = empty($filelist) ? [] : $filelist;

		if($sort == "sortByNameDesc" or $sort == "sortByNameAsc") {
			usort($filelist, function($a, $b) use ($sort) {
				if($sort == "sortByNameDesc")
					return pathinfo($a, PATHINFO_BASENAME) > pathinfo($b, PATHINFO_BASENAME);
				else
					return pathinfo($a, PATHINFO_BASENAME) < pathinfo($b, PATHINFO_BASENAME);
			});
		}
		if($sort == "sortBySizeDesc" or $sort == "sortBySizeAsc") {
			usort($filelist, function($a, $b) use ($sort) {
				if($sort == "sortByNameDesc")
					return filesize($a) > filesize($b);
				else
					return filesize($a) < filesize($b);
			});
		}

		//get subset of file array
		$selectedFiles = array_slice($filelist, $offset, $limit);
		$datas = [];
		
		$totalPages = ceil(count($filelist) / $limit);
		$conn = $em->getConnection();
		
		foreach($selectedFiles as $pathFile) {
			$file = pathinfo($pathFile, PATHINFO_BASENAME);
			
			$rootFolder = str_replace([$basePath, $photoPath.DIRECTORY_SEPARATOR.$selectedFolder], "", pathinfo($pathFile, PATHINFO_DIRNAME));

			if(is_dir($pathFile))
				continue;

			$contentFile = file_get_contents($pathFile);
			$finfo = finfo_open();
			$mime_type = finfo_buffer($finfo, $contentFile, FILEINFO_MIME_TYPE);
			finfo_close($finfo);
			
			$type = match($mime_type) {
				'image/png', 'image/jpg', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/webp', "image/bmp" => "image",
				"video/webm", "video/x-msvideo", "video/mpeg", "video/ogg" => "video",
				"audio/webm", "audio/x-wav", "audio/ogg", "audio/aac" => "audio",
				"application/pdf" => "file",
				default => "other"
			};

			$src = $type == "other" ? $contentFile : "data:".$mime_type.";base64," . base64_encode($contentFile);
			$res = [];

			switch($selectedFolder) {
				case "album":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Album_Admin_Show\" as route
						FROM album n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".addslashes($file)."';");
				break;
				case "banner":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Banner_Admin_Show\" as route
						FROM banner n
						WHERE n.image = '".$file."';");
				break;
				case "advertising":
					$regex = "\[\/\"\']{1}".$file."[\'\"]{1}";
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Advertising_Admin_Show\" as route
						FROM advertising n
						WHERE n.text REGEXP '".$regex."';");
				break;
				case "movie\genreaudiovisual":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"GenreAudiovisual_Admin_Show\" as route
						FROM GenreAudiovisual n
						WHERE n.photo = '".$file."';");
				break;
				case "biography":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Biography_Admin_Show\" as route
						FROM biography n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "blog":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Book_Admin_Show\" as route
						FROM blog n
						WHERE n.banner = '".$file."';");
				break;
				case "book":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Book_Admin_Show\" as route
						FROM book n
						WHERE n.photo = '".$file."';");
				break;
				case "book\publisher":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Publisher_Admin_Show\" as route
						FROM book n
						WHERE n.photo = '".$file."';");
				break;
				case "cartography":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Cartography_Admin_Show\" as route
						FROM cartography n
						WHERE n.photo = '".$file."';");
				break;
				case "country":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Region_Admin_Show\" as route
						FROM region n
						WHERE n.flag = '".$file."';");
				break;
				case "document":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Document_Admin_Show\" as route
						FROM document n
						WHERE n.pdfDoc = '".$file."';");
				break;
				case "eventMessage":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"EventMessage_Admin_Show\" as route
						FROM eventMessage n
						WHERE n.photo = '".$file."' OR n.thumbnail = '".$file."';");
				break;
				case "language":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Language_Admin_Show\" as route
						FROM language n
						WHERE n.logo = '".$file."';");
				break;
				case "licence":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Licence_Admin_Show\" as route
						FROM licence n
						WHERE n.logo = '".$file."';");
				break;
				case "music":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.musicPiece, \"Music_Admin_Show\" as route
						FROM music n
						WHERE n.musicPieceFile = '".$file."';");
				break;
				case "news":
					$regex = "\[\/\"\']{1}".$file."[\'\"]{1}";
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"News_Admin_Show\" as route
						FROM news n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE n.text REGEXP '".$regex."' 
						OR fm.realNameFile = '".$file."';");
				break;
				case "page":
					$regex = "\[\/\"\']{1}".$file."[\'\"]{1}";
					$res = array_merge($conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Page_Admin_Show\" as route
						FROM page n
						WHERE n.text REGEXP '".$regex."';"),
						$conn->fetchAllAssociative("
						SELECT n.id, n.title, \"President_Admin_Show\" as route
						FROM president n
						WHERE n.photo = '".$file."';")
						);
				break;
				case "partner":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Partner_Admin_Show\" as route
						FROM partner n
						WHERE n.text REGEXP '".$regex."';");
				break;
				case "quotation":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.textQuotation, \"Quotation_Admin_Show\" as route
						FROM quotation n
						LEFT JOIN quotationimage fm ON n.id = fm.quotation_id
						WHERE fm.image = '".$file."';");
				break;
				case "photo":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Photo_Admin_Show\" as route
						FROM photo n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "testimony":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title
						FROM testimony n
						JOIN testimonyfilemanagement tfm ON n.id = tfm.testimony_id
						JOIN filemanagement fm ON fm.id = tfm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "theme":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Theme_Admin_Show\" as route
						FROM theme n
						WHERE n.photo = '".$file."' OR n.pdfTheme = '".$file."';");
				break;
				case "user":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.username AS title, \"apadminuser_show\" as route
						FROM ap_user n
						WHERE n.avatar = '".$file."';");
				break;
				case "video":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Video_Admin_Show\" as route
						FROM video n
						WHERE n.photo = '".$file."';");
				break;
				case "webdirectory":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"WebDirectory_Admin_Show\" as route
						FROM webdirectory n
						WHERE n.photo = '".$file."';");
				break;
				case "tag":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Movie_Admin_Show\" as route
						FROM tagword n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "movie":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Movie_Admin_Show\" as route
						FROM movie n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "witchcraft":
					$res = 
						$conn->fetchAllAssociative("
						SELECT n.id, n.title, \"MenuGrimoire_Admin_Show\" as route
						FROM menugrimoire n
						WHERE n.photo = '".$file."';");
				break;
				case "witchcraft\grimoire":
					$res = $conn->fetchAllAssociative("
						SELECT n.id, n.title, \"Grimoire_Admin_Show\" as route
						FROM grimoire n
						WHERE n.photo = '".$file."';");
				break;
				default:
					break;
			}

			$datas[] = ["src" => $src, "res" => $res, "file" => $file, "size" => $imgSize->getFileSize($pathFile), "pathFile" => $pathFile, "rootFolder" => $rootFolder, "type" => $type];
		}

		return $this->render("filemanagement/FileManagementAdmin/listFiles.html.twig", [
			"total" => count($filelist),
			"selectedFiles" => $datas,
			"listFolders" => $listFolders,
			"totalPages" => $totalPages,
			"folder" => $selectedFolder,
			"page" => $page,
			"mimeTypes" => $this->mimeTypes
		]); 
	}
	
	public function deleteFileAction(Request $request)
	{
		unlink($request->query->get("pathFile"));
		
		return $this->redirect($this->generateUrl("FileManagement_Admin_ListFiles", ["page" => $request->query->get("page"), "folder" => $request->query->get("folder")]));
	}
	
	public function moveFileAction(Request $request, $rootFolder)
	{
		$file = $request->query->get("pathFile");
		$newFile = str_replace(DIRECTORY_SEPARATOR.($rootFolder == "public" ? "private" : "public").DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR.$rootFolder.DIRECTORY_SEPARATOR, $file);

		if(!is_dir(pathinfo($newFile, PATHINFO_DIRNAME)))
			mkdir(pathinfo($newFile, PATHINFO_DIRNAME));

		$res = rename($file, $newFile);

		return $this->redirect($this->generateUrl("FileManagement_Admin_ListFiles", ["page" => $request->query->get("page"), "folder" => $request->query->get("folder")]));
	}

    public function showImageAction(EntityManagerInterface $em, $idClassName, $className)
    {
		list($entity, $classNameFileManagement) = $this->getNewEntity($em, $className, $idClassName);
		$entities = $em->getRepository($classNameFileManagement)->getAllFilesForTestimonyByIdClassName($idClassName);

        return $this->render('filemanagement/FileManagementAdmin/showImage.html.twig', [
			'entities' => $entities,
			'idClassName' => $idClassName,
			'className' => $className,
			'mainEntity' => $em->getRepository($entity->getMainEntityClassName())->find($idClassName)
        ]);
    }

	public function compressFile(Request $request, ResmushIt $resmushIt, APImgSize $imgSize) {
		$filename = $request->query->get("file");

		$res = $resmushIt->compressFromData(file_get_contents($filename), basename($filename));

		if(strlen(file_get_contents($filename)) > strlen($res))
			file_put_contents($filename, $res);

		return new JsonResponse(["size" => $imgSize->getFileSize($filename)]);
	}

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
	
	private function listFolders($base_dir, $bd){
      $directories = [];

      foreach(scandir($base_dir) as $file) {
            if($file == '.' || $file == '..') continue;
			
			$dir = $base_dir.DIRECTORY_SEPARATOR.$file;
			
            if(is_dir($dir)) {
                $directories[] = str_replace($bd.DIRECTORY_SEPARATOR, "", $dir);
                $directories = array_merge($directories, $this->listFolders($dir, $bd));
            }
      }

      return $directories;
	}

	private function getDirContents($dir, &$results = [], $mimeType = null) {
		if (!is_dir($dir))
			return [];

		$files = scandir($dir);

		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path)) {
				if(empty($mimeType))
					$results[] = $path;
				else {
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mime = finfo_file($finfo, $path);

					if(($mime == $mimeType and $mimeType != "other") or ($mimeType == "other" and !in_array($mime, $this->mimeTypes)))
						$results[] = $path;
				}
			} else if ($value != "." && $value != "..") {
				$this->getDirContents($path, $results);
				$results[] = $path;
			}
		}

		return $results;
	}
}