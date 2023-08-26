<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

use App\Entity\Region;
use Ausi\SlugGenerator\SlugGenerator;

#[AsCommand(
   name: 'app:migrate-region'
)]
class RegionCommand extends Command
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
		$output->writeln("Start News migration");
		
		$wts = $this->em->getRepository(Region::class)->findAll();
		
		foreach($wts as $wt) {
			if(empty($wt->getFamily())) {
				$wt->setFamily(Region::COUNTRY_FAMILY);

				$this->em->persist($wt);
			}
		}

		$this->em->flush();

        return 0;
    }
}