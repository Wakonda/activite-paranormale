<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use App\Service\SearchEngine;

use App\Entity\Interfaces\SearchEngineInterface;

#[AsCommand(
   name: 'app:search-engine'
)]
class SearchEngineCommand extends Command
{
    private $em;
	private $searchEngine;
	private $parameterBag;

    public function __construct(EntityManagerInterface $em, SearchEngine $searchEngine, ParameterBagInterface $parameterBag)
    {
		parent::__construct();
        $this->em = $em;
        $this->searchEngine = $searchEngine;
		$this->parameterBag = $parameterBag;
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
		$output->writeln("Start Search engine indexation");
		
		$entities = array();
		
		$filename = $this->parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."search".DIRECTORY_SEPARATOR."ap.index";

		$this->searchEngine->setParams($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $filename, null);

		$this->searchEngine->init($filename);

		$meta = $this->em->getMetadataFactory()->getAllMetadata();
		foreach ($meta as $m) {
			$c = $m->getName();

			if(new $c() instanceof SearchEngineInterface) {
				$qb = $this->em->createQueryBuilder();

				$reflectionClass = new \ReflectionClass($c);
				$annotationReader = new \Doctrine\Common\Annotations\AnnotationReader();

				$anno = array_map(function($e) { return get_class($e); }, $annotationReader->getClassAnnotations($reflectionClass));

				if (in_array(\Doctrine\ORM\Mapping\MappedSuperclass::class, $anno))
					continue;

				$datas = $qb->select("e")
				   ->from($m->getName(), "e")
				   ->join("e.state", "s")
				   ->where("s.displayState = 1")
				   ->andWhere("e.archive = false")
				   ->getQuery()->getResult();

				foreach($datas as $data) {
					$classname = $this->em->getClassMetadata(get_class($data))->getTableName();
					
					$query = "SELECT n.id, l.abbreviation AS language, n.title, n.text 
					FROM {$classname} n
					INNER JOIN language l ON n.language_id = l.id
					WHERE n.id = {$data->getId()}";
					
					$this->searchEngine->run($filename, $query, $classname);
					
					$query = "SELECT n.id, l.abbreviation AS language, i.realNameFile, n.text 
					FROM {$classname} n
					INNER JOIN language l ON n.language_id = l.id
					INNER JOIN filemanagement i ON n.illustration_id = i.id
					WHERE n.id = {$data->getId()}";

					$this->searchEngine->runImage($filename, $query, $classname);
				}
			}
		}

        return 0;
    }
}