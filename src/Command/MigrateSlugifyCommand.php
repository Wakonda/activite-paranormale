<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Ausi\SlugGenerator\SlugGenerator;

#[AsCommand(
   name: 'app:migrate-slugify'
)]
class MigrateSlugifyCommand extends Command
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
		$output->writeln("Start publisher migration");

		$conn = $this->em->getConnection();

		$generator = new SlugGenerator;
		$entities = $this->em->getRepository("\App\Entity\Album")->findAll();
		
		foreach($entities as $entity) {
			$conn->exec("UPDATE Album SET slug = '".$generator->generate($entity->getTitle())."' WHERE id = ".$entity->getId());
		}

		$entities = $this->em->getRepository("\App\Entity\Artist")->findAll();
		
		foreach($entities as $entity) {
			$conn->exec("UPDATE artist SET slug = '".$generator->generate($entity->getTitle())."' WHERE id = ".$entity->getId());
		}

		$entities = $this->em->getRepository("\App\Entity\Music")->findAll();
		
		foreach($entities as $entity) {
			$conn->exec("UPDATE Music SET slug = '".$generator->generate($entity->getTitle())."' WHERE id = ".$entity->getId());
		}

        return 0;
    }
}