<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
   name: 'app:biography-nationality'
)]
class MigrateBiographyNationalityCommand extends Command
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
		$output->writeln("Start publisher migration");

		$conn = $this->em->getConnection();
		
		$entities = $this->em->getRepository("\App\Entity\Biography")->findAll();

		foreach($entities as $entity) {
			if(!empty($entity->getNationality())) {
				if($entity->getLanguage()->getId() != $entity->getNationality()->getLanguage()->getId()) {
					$nationality = $this->em->getRepository("\App\Entity\Region")->findOneBy(["language" => $entity->getLanguage(), "internationalName" => $entity->getNationality()->getInternationalName()]);
					$entity->setNationality($nationality);
					$this->em->persist($entity);
				}
			}
		}
		
		$this->em->flush();

        return 0;
    }
}