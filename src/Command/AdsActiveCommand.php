<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Book;
use App\Entity\Cartography;
use App\Entity\Document;
use App\Entity\EventMessage;
use App\Entity\Movies\Movie;
use App\Entity\Movies\TelevisionSerie;
use App\Entity\Photo;
use App\Entity\Testimony;
use App\Entity\Video;
use App\Entity\Grimoire;
use App\Entity\News;
use App\Entity\Stores\Store;
use App\Entity\Licence;
use App\Entity\WebDirectory;
use App\Entity\Quotation;
use App\Entity\WitchcraftTool;

#[AsCommand(
   name: 'app:ads-active'
)]
class AdsActiveCommand extends Command
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
		$output->writeln("Start Activate Ads");
		
		$conn = $this->em->getConnection();
		$conn->exec("UPDATE Advertising SET active = true;");

        return 0;
    }
	
	private function isPost($categories): bool 
	{
		foreach($categories as $category)
			if(parse_url($category->getAttribute("term"), PHP_URL_FRAGMENT) == "post")
				return true;

		return false;
	}
}