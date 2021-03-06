<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\TestimonyFileManagement;
use App\Form\Type\FileManagementEditType;

class FileManagementAdminController extends AbstractController
{
	public function listFilesAction(Request $request)
	{
		$limit = 9;
		
		$basePath = $this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR;
		$photoPath = DIRECTORY_SEPARATOR."extended".DIRECTORY_SEPARATOR."photo";

		$folderPublic = realpath($basePath."public".$photoPath);
		$folderPrivate = realpath($basePath."private".$photoPath );

		$listFolders = array_unique(array_merge($this->listFolders($folderPublic, $folderPublic), $this->listFolders($folderPrivate, $folderPrivate)));

		$selectedFolder = $request->query->get("folder", "news");
		$page = $request->query->get("page", 1);
		
		$offset = ($page - 1) * $limit;
		
		$this->getDirContents($folderPublic.DIRECTORY_SEPARATOR.$selectedFolder, $filelist);
		$this->getDirContents($folderPrivate.DIRECTORY_SEPARATOR.$selectedFolder, $filelist);

		usort($filelist, function($a, $b) {
			return pathinfo($a, PATHINFO_BASENAME) > pathinfo($b, PATHINFO_BASENAME);
		});

		//get subset of file array
		$selectedFiles = array_slice($filelist, $offset, $limit);
		$datas = [];
		
		$totalPages = ceil(count($filelist) / $limit);
		$conn = $this->getDoctrine()->getManager()->getConnection();
		
		foreach($selectedFiles as $pathFile) {
			$file = pathinfo($pathFile, PATHINFO_BASENAME);
			
			$rootFolder = str_replace([$basePath, $photoPath.DIRECTORY_SEPARATOR.$selectedFolder], "", pathinfo($pathFile, PATHINFO_DIRNAME));

			if(is_dir($pathFile))
				continue;
			
			$contentFile = file_get_contents($pathFile);
			$finfo = finfo_open();
			$mime_type = finfo_buffer($finfo, $contentFile, FILEINFO_MIME_TYPE);
			finfo_close($finfo);
			
			$src = "data:".$mime_type.";base64," . base64_encode($contentFile);
			$res = [];

			switch($selectedFolder) {
				case "album":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Album_Admin_Show\" as route
						FROM album n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".addslashes($file)."';");
				break;
				case "banner":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Banner_Admin_Show\" as route
						FROM banner n
						WHERE n.image = '".$file."';");
				break;
				case "movie\genreaudiovisual":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"GenreAudiovisual_Admin_Show\" as route
						FROM GenreAudiovisual n
						WHERE n.photo = '".$file."';");
				break;
				case "biography":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Biography_Admin_Show\" as route
						FROM biography n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "blog":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Book_Admin_Show\" as route
						FROM blog n
						WHERE n.banner = '".$file."';");
				break;
				case "book":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Book_Admin_Show\" as route
						FROM book n
						WHERE n.photo = '".$file."';");
				break;
				case "book\publisher":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Publisher_Admin_Show\" as route
						FROM book n
						WHERE n.photo = '".$file."';");
				break;
				case "cartography":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Cartography_Admin_Show\" as route
						FROM cartography n
						WHERE n.photo = '".$file."';");
				break;
				case "country":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Country_Admin_Show\" as route
						FROM country n
						WHERE n.flag = '".$file."';");
				break;
				case "document":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Document_Admin_Show\" as route
						FROM document n
						WHERE n.pdfDoc = '".$file."';");
				break;
				case "eventMessage":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"EventMessage_Admin_Show\" as route
						FROM eventMessage n
						WHERE n.photo = '".$file."' OR n.thumbnail = '".$file."';");
				break;
				case "language":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Language_Admin_Show\" as route
						FROM language n
						WHERE n.logo = '".$file."';");
				break;
				case "licence":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Licence_Admin_Show\" as route
						FROM licence n
						WHERE n.logo = '".$file."';");
				break;
				case "music":
					$res = $conn->fetchAll("
						SELECT n.id, n.musicPiece, \"Music_Admin_Show\" as route
						FROM music n
						WHERE n.musicPieceFile = '".$file."';");
				break;
				case "news":
					$regex = "\[\/\"\']{1}".$file."[\'\"]{1}";
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"News_Admin_Show\" as route
						FROM news n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE n.text REGEXP '".$regex."' 
						OR fm.realNameFile = '".$file."';");
				break;
				case "page":
					$regex = "\[\/\"\']{1}".$file."[\'\"]{1}";
					$res = array_merge($conn->fetchAll("
						SELECT n.id, n.title, \"Page_Admin_Show\" as route
						FROM page n
						WHERE n.text REGEXP '".$regex."';"),
						$conn->fetchAll("
						SELECT n.id, n.title, \"President_Admin_Show\" as route
						FROM president n
						WHERE n.photo = '".$file."';")
						);
				break;
				case "partner":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Partner_Admin_Show\" as route
						FROM partner n
						WHERE n.text REGEXP '".$regex."';");
				break;
				case "quotation":
					$res = $conn->fetchAll("
						SELECT n.id, n.textQuotation, \"Quotation_Admin_Show\" as route
						FROM quotation n
						LEFT JOIN quotationimage fm ON n.id = fm.quotation_id
						WHERE fm.image = '".$file."';");
				break;
				case "photo":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Photo_Admin_Show\" as route
						FROM photo n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "testimony":
					$res = $conn->fetchAll("
						SELECT n.id, n.title
						FROM testimony n
						JOIN testimonyfilemanagement tfm ON n.id = tfm.testimony_id
						JOIN filemanagement fm ON fm.id = tfm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "theme":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Theme_Admin_Show\" as route
						FROM theme n
						WHERE n.photo = '".$file."' OR n.pdfTheme = '".$file."';");
				break;
				case "user":
					$res = $conn->fetchAll("
						SELECT n.id, n.username AS title, \"apadminuser_show\" as route
						FROM ap_user n
						WHERE n.avatar = '".$file."';");
				break;
				case "video":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Video_Admin_Show\" as route
						FROM video n
						WHERE n.photo = '".$file."';");
				break;
				case "webdirectory":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"WebDirectory_Admin_Show\" as route
						FROM webdirectory n
						WHERE n.photo = '".$file."';");
				break;
				case "tag":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Movie_Admin_Show\" as route
						FROM tagword n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "movie":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Movie_Admin_Show\" as route
						FROM movie n
						LEFT JOIN filemanagement fm ON n.illustration_id = fm.id
						WHERE fm.realNameFile = '".$file."';");
				break;
				case "witchcraft":
					$res = array_merge(
						$conn->fetchAll("
						SELECT n.id, n.title, \"MenuGrimoire_Admin_Show\" as route
						FROM menugrimoire n
						WHERE n.photo = '".$file."';"),
						$conn->fetchAll("
						SELECT n.id, n.title, \"SurThemeGrimoire_Admin_Show\" as route
						FROM surthemegrimoire n
						WHERE n.photo = '".$file."';"));
				break;
				case "witchcraft\grimoire":
					$res = $conn->fetchAll("
						SELECT n.id, n.title, \"Grimoire_Admin_Show\" as route
						FROM grimoire n
						WHERE n.photo = '".$file."';");
				break;
				default:
					break;
			}

			$datas[] = ["src" => $src, "res" => $res, "file" => $file, "pathFile" => $pathFile, "rootFolder" => $rootFolder];
		}

		return $this->render("filemanagement/FileManagementAdmin/listFiles.html.twig", [
			"total" => count($filelist),
			"selectedFiles" => $datas,
			"listFolders" => $listFolders,
			"totalPages" => $totalPages,
			"folder" => $selectedFolder,
			"page" => $page
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
	
	private function listFolders($base_dir, $bd){
      $directories = array();
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

	private function getDirContents($dir, &$results = array()) {
		if (!is_dir($dir))
			return [];

		$files = scandir($dir);

		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path)) {
				$results[] = $path;
			} else if ($value != "." && $value != "..") {
				$this->getDirContents($path, $results);
				$results[] = $path;
			}
		}

		return $results;
	}
}