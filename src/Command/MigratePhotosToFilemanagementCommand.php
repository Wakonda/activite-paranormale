<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\News;
use App\Entity\Photo;
use App\Entity\Biography;
use App\Entity\FileManagement;

#[AsCommand(
   name: 'app:migrate-photos'
)]
class MigratePhotosToFilemanagementCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
		$output->writeln("Start TagWord migration");

		$conn = $this->em->getConnection();

		$sql = "SELECT id, photo FROM tagword WHERE illustration_id IS NULL";
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE tagword SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		die("End TagWord");

		$conn = $this->em->getConnection();

		$sql = "SELECT id, photo FROM movie WHERE illustration_id IS NULL";
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE movie SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		die("End movie");
		
		$output->writeln("Start News migration");

		$sql = "SELECT id, mediaNew FROM news WHERE illustration_id IS NULL";
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['mediaNew'])."', '".str_replace("'", "\'", $data['mediaNew'])."', '".pathinfo(str_replace("'", "\'", $data['mediaNew']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE news SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}

        $output->writeln("End News migration");
		
		$output->writeln("Start Biography migration");

		$sql = "SELECT id, photo FROM biography WHERE illustration_id IS NULL";
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE biography SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}

        $output->writeln("End Biography migration");
		
        $output->writeln("Start Photo migration");

		$sql = "SELECT id, photo FROM photo WHERE illustration_id IS NULL";
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE photo SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}

        $output->writeln("End Photo migration");
		
		// Source new transformation
		$conn->exec("UPDATE news set sourceNew = CONCAT('[{\"author\":\"\",\"title\":\"\",\"url\":\"', sourceNew, '\",\"type\":\"url\"}]') WHERE sourceNew REGEXP \"^(https?://|www\\.)[\.A-Za-z0-9\-]+\\.[a-zA-Z]{2,4}\"");
		$conn->exec("UPDATE quotation set source = CONCAT('[{\"author\":\"\",\"isbn10\":\"\",\"isbn13\":\"\",\"date\":\"\",\"publisher\":\"\",\"title\":\"\", source, '\",\"type\":\"work\"}]')");

        return 0;
    }
}