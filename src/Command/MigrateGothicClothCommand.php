<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Stores\Store;

class MigrateGothicClothCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:migrate-gothic-cloth';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start Book migration");

		$conn = $this->em->getConnection();
		
		$conn->exec("UPDATE Store SET category = 'gothicCloth' WHERE category = 'cloth';");

        return 0;
    }
}