<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\WebDirectory;
use App\Entity\FileManagement;
use Ausi\SlugGenerator\SlugGenerator;

#[AsCommand(
   name: 'app:migrate-update-webdirectory-illustration'
)]
class MigrateWebDirectoryLogosToFilemanagementCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start WebDirectory migration");

		$conn = $this->em->getConnection();

		$sql = "SELECT id, logo FROM WebDirectory WHERE illustration_id IS NULL";
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['logo'])."', '".str_replace("'", "\'", $data['logo'])."', '".pathinfo(str_replace("'", "\'", $data['logo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchOne("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE WebDirectory SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		die("End WebDirectory");

        return 0;
    }
}