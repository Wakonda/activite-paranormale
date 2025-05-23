<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\EventMessage;

#[AsCommand(
   name: 'app:migrate-date-event'
)]
class MigrateDateEventCommand extends Command
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
		$output->writeln("Start Event migration");

		$conn = $this->em->getConnection();

		$sql = "SELECT id, datefrom, dateto FROM eventmessage";
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data)
		{
			if(!empty($data["datefrom"]))
				list($yearFrom, $monthFrom, $dayFrom) = explode("-", $data["datefrom"]);
			else {
				$yearFrom = "null";
				$monthFrom = "null";
				$dayFrom = "null";
			}
			
			if(!empty($data["dateto"]))
				list($yearTo, $monthTo, $dayTo) = explode("-", $data["dateto"]);
			else {
				$yearTo = "null";
				$monthTo = "null";
				$dayTo = "null";
			}

			$conn->exec("UPDATE eventmessage SET yearFrom = ".$yearFrom.", monthFrom = ".$monthFrom.", dayFrom = ".$dayFrom.", yearTo = ".$yearTo.", monthTo = ".$monthTo.", dayTo = ".$dayTo." WHERE id = ".$data["id"]);
		}
		
		echo "End Event";

		$conn->exec("UPDATE eventmessage SET type = 'convention'");

        return 0;
    }
}