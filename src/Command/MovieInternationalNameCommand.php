<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Movies\Movie;
use Ausi\SlugGenerator\SlugGenerator;

class MovieInternationalNameCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:movie-international-name';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start News migration");
		
		$movies = $this->em->getRepository(Movie::class)->findAll();
		
		foreach($movies as $movie) {
			$generator = new SlugGenerator;
			$in = $generator->generate($movie->getTitle()).uniqid();
			$movie->setInternationalName($in);
			$this->em->persist($movie);
		}

		$this->em->flush();

        return 0;
    }
}