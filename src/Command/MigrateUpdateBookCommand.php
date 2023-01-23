<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Book;
use App\Entity\EventMessage;
use App\Entity\LiteraryGenre;
use App\Entity\FileManagement;

class MigrateUpdateBookCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:migrate-update-book';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start Book migration");

		$conn = $this->em->getConnection();
		
		
		$conn->exec("UPDATE blog SET category = 'blog';");
		
		// OK
		/*$conn->exec("UPDATE movie SET reviewScores = null WHERE reviewScores = 'undefined';");

		$sql = "SELECT id, photo FROM president WHERE illustration_id IS NULL";
		$datas = $conn->fetchAll($sql);

		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE president SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		echo "End president";

		// OK
		$datas = $this->em->getRepository(EventMessage::class)->findAll();
		
		foreach($datas as $data) {
			$generator = new \Ausi\SlugGenerator\SlugGenerator;
			$data->setInternationalName($generator->generate($data->getTitle()).uniqid());
			$this->em->persist($data);
		}
		
		$this->em->flush();

		// OK
		$genres = $this->em->getRepository(LiteraryGenre::class)->findBy(["wikidata" => "Q35760"]);
		$genreArray = [];
		
		foreach($genres as $genre) {
			$genreArray[$genre->getLanguage()->getAbbreviation()] = $genre;
		}
		$books = $this->em->getRepository(Book::class)->findAll();

		foreach($books as $book) {
			if(!isset($genreArray[$book->getLanguage()->getAbbreviation()]))
				continue;
			
			$book->setGenre($genreArray[$book->getLanguage()->getAbbreviation()]);
			$this->em->persist($book);
		}

		$this->em->flush();*/

        return 0;
    }
}