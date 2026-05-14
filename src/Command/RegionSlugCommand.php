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

use Ausi\SlugGenerator\SlugGenerator;

#[AsCommand(
   name: 'app:region-slug'
)]
class RegionSlugCommand extends Command
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
		$output->writeln("Start Region slug");

		$conn = $this->em->getConnection();

		$generator = new SlugGenerator;
		$wts = $this->em->getRepository("\App\Entity\Region")->findAll();
		
		foreach($wts as $wt) {
			$conn->executeQuery("UPDATE region SET slug = '".$generator->generate($wt->getTitle())."' WHERE id = ".$wt->getId());
		}
		
		$conn->executeQuery("UPDATE region set internationalname = wikidata where wikidata is not null and family = 'city';");
		
        return 0;
    }
}