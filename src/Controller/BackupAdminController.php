<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Attribute\Route;

use Ifsnop\Mysqldump as IMysqldump;

class BackupAdminController extends AbstractController
{
	#[Route('/admin/backup/index', name: 'Backup_Admin_Index')]
    public function index()
    {
		$files = [];
		
		if(is_dir($this->getPath())) {
			if ($handle = opendir($this->getPath())) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						$files[] = $entry;
					}
				}

				closedir($handle);
			}
		}

        return $this->render('admin/BackupAdmin/index.html.twig', ["files" => $files]);
    }

	#[Route('/admin/backup/download/{filename}', name: 'Backup_Admin_Download')]
	public function downloadAction($filename)
	{
		$response = new BinaryFileResponse($this->getPath().DIRECTORY_SEPARATOR.$filename);
		$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

		return $response;
	}

	#[Route('/admin/backup/delete/{filename}', name: 'Backup_Admin_Delete')]
	public function deleteAction(TranslatorInterface $translator, $filename)
	{
		unlink($this->getPath().DIRECTORY_SEPARATOR.$filename);

		$this->addFlash('success', $translator->trans('backup.index.FileDeleted', [], 'validators'), [], 'validators');
		
		return $this->redirect($this->generateUrl("Backup_Admin_Index"));
	}

	#[Route('/admin/backup/generate', name: 'Backup_Admin_Generate')]
	public function generate(TranslatorInterface $translator)
	{
		try {
			$dsn = "mysql:host=".$_ENV['DB_HOST'].";dbname=".$_ENV['DB_NAME'];

			if(!is_dir($this->getPath())) {
				mkdir($this->getPath());
			}
			
			$filename = "backup_" . date("Y_m_d_H_i_s") . ".sql";

			$dump = new IMysqldump\Mysqldump($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
			$dump->start($this->getPath().DIRECTORY_SEPARATOR.$filename);
			
			$this->addFlash('success', $translator->trans('backup.index.FileGenerated', [], 'validators'), [], 'validators');
		} catch (\Exception $e) {
			$this->addFlash('success', 'mysqldump-php error: ' . $e->getMessage(), [], 'validators');
		}

		return $this->redirect($this->generateUrl("Backup_Admin_Index"));
	}

	public function countAction()
	{
		$count = 0;

		if(is_dir($this->getPath())) {
			$fi = new \FilesystemIterator($this->getPath(), \FilesystemIterator::SKIP_DOTS);
			$count = iterator_count($fi);
		}
		
		return new Response($count);
	}

	private function getPath()
	{
		return $this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR."var".DIRECTORY_SEPARATOR."backup";
	}
}