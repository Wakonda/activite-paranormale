<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\State;
use App\Entity\Language;

#[AsCommand(
   name: 'app:web-directory'
)]
class WebDirectoryCommand extends Command
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
		$output->writeln("Start Archive migration");

		$conn = $this->em->getConnection();

		$datas = $this->em->getRepository("\App\Entity\WebDirectory")->findAll();
		
		foreach($datas as $data) {
			$language = $data->getLanguage();
			$state = $this->em->getRepository(State::class)->findOneBy(["internationalName" => "Validate", "language" => $language]);
			
			if(empty($state)) {
				$language = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
				$state = $this->em->getRepository(State::class)->findOneBy(["internationalName" => "Validate", "language" => $language]);
			}
			$data->setState($state);

			$this->em->persist($data);
		}
		
		$this->em->flush();
		
        return 0;
    }
}