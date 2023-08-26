<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

use App\Entity\FileManagement;

#[AsCommand(
   name: 'app:filemanagement-license'
)]
class FilemanagementLicenseCommand extends Command
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
		$output->writeln("Start TagWord migration");

		$conn = $this->em->getConnection();

		$sql = "SELECT id, caption FROM filemanagement WHERE caption IS NOT NULL";
		$datas = $conn->fetchAll($sql);
		
		foreach($datas as $data)
		{
			if(strpos($data["caption"], 'Pixabay') !== false) {
				$a = new \SimpleXMLElement($data["caption"]);
				die(var_dump($data, $data["caption"], $a['href']));
				var_dump(strip_tags($data["caption"]));
			}
			/*if(strpos($data["caption"], 'Wikimedia') !== false) {
				$a = new \SimpleXMLElement($data["caption"]);
				die(var_dump($a['href']));
				var_dump(strip_tags($data["caption"]));
			}*/
		}

        return 0;
    }
}