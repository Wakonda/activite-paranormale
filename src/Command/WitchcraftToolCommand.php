<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\WitchcraftTool;
use Ausi\SlugGenerator\SlugGenerator;

class WitchcraftToolCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:migrate-witchcraft-tool';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start News migration");
		
		$wts = $this->em->getRepository(WitchcraftTool::class)->findAll();
		
		foreach($wts as $wt) {
			$wt->setPhoto(null);
			$wt->setSlug();
			
			$this->em->persist($wt);
			$this->em->flush();
		}
		

        return 0;
    }
}