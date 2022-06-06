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

class InternationalizationCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:internationalization';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$conn = $this->em->getConnection();
		
		$sql = "select b.id, b.language_id, 
				(select c2.internationalName from biography b2 join country c2 on c2.id = b2.nationality_id where b2.internationalName = b.internationalName group by c2.internationalName) as nationality
				from biography b
				where nationality_id is null
				AND b.internationalName in (select * from (select b1.internationalName from biography b1 where b1.nationality_id is not null) as t);";
		$datas = $conn->fetchAll($sql);

		foreach($datas as $data) {
			$sqlCountry = "SELECT id AS id FROM country WHERE language_id = ".$data["language_id"]." AND internationalName = '".$data["nationality"]."'";
			$dataCountry = $conn->fetchAssoc($sqlCountry);

			if($dataCountry)
				$conn->exec("UPDATE biography SET nationality_id = ".$dataCountry["id"]." WHERE id = ".$data["id"]);
		}

        return 0;
    }
}