<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\FileManagement;
use App\Entity\Biography;
use App\Entity\Book;
use App\Entity\BookEdition;
use App\Entity\Movies\Movie;
use App\Entity\Movies\MovieBiography;
use App\Entity\Stores\MovieStore;
use App\Entity\Stores\Store;
use App\Entity\Stores\BookStore;
use App\Entity\Stores\AlbumStore;
use App\Entity\Album;

class NewArchiveCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:new-archive';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$conn = $this->em->getConnection();
		
		$stmt = $conn->prepare("UPDATE grimoire SET publicationDate = writingDate;");
        $stmt->execute();

        return 0;
    }
}