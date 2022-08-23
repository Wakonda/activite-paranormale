<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Grimoire;
use App\Entity\EventMessage;
use App\Entity\FileManagement;

class MigratePhotosGrimoireEventToFilemanagementCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:migrate-photos-ge';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start TagWord migration");

		$conn = $this->em->getConnection();

		$sql = "SELECT id, photo FROM eventmessage WHERE illustration_id IS NULL";
		$datas = $conn->fetchAll($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE eventmessage SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		die("End eventmessage");

		$sql = "SELECT id, photo FROM grimoire WHERE illustration_id IS NULL";
		$datas = $conn->fetchAll($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE grimoire SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		die("End grimoire");

        return 0;
    }
}