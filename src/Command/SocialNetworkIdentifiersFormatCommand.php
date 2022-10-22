<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

class SocialNetworkIdentifiersFormatCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:social-network-identifiers-format';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start Event migration");

		$conn = $this->em->getConnection();
		
		$datas = $this->em->getRepository(WebDirectory::class)->findAll();
		
		foreach($datas as $data) {
			if(empty($data->getInternationalName())) {
				$generator = new \Ausi\SlugGenerator\SlugGenerator;
				$data->setInternationalName($generator->generate($data->getTitle()).uniqid());
			}
			
			if(empty($data->getWebsiteLanguage())) {
				$data->setWebsiteLanguage($data->getLanguage());
			}

			$this->em->persist($data);
		}
		
		$this->em->flush();
		die("eeee");
		
		
		$datas = $this->em->getRepository(Licence::class)->findAll();
		
		foreach($datas as $data)
		{
			preg_match('/^CC-BY[A-Z-]*|^CC0/', $data->getTitle(), $matches);
		
			if(empty($matches))
				continue;
	
			if($matches[0] == "CC0")
				$title = "CC0 1.0";
			else
				$title = $matches[0]." 3.0";
			
			$data->setTitle($title);
			$data->setInternationalName($title);

			$this->em->persist($data);
		}
		
		$this->em->flush();
die("ooo");
		$urlId = [
			"jakin-boaz" => "4192778394306065291",
			"bookoflucifer" => "8587307742034849671",
			"prieres-et-sortileges" => "1611333979864196065",
			"amatukami" => "3616068588689105914",
			"wakonda666" => "976120769055861867",
			"activite-paranormale" => "6843544030232757764",
			"thetempleofzebuleon" => "3619394577589453859",
			"elgrimoriodeastaroth" => "6143285371855196758",
			"testap7" => "2865018866226462436"
		];
		
		$tables = [
			Book::class,
			Cartography::class,
			Document::class,
			EventMessage::class,
			Movie::class,
			TelevisionSerie::class,
			Photo::class,
			Testimony::class,
			Video::class,
			Grimoire::class,
			News::class,
			Store::class
		];

		foreach($tables as $table) {
			$qb = $this->em->createQueryBuilder();

			$datas = $qb->select("e")
			   ->from($table, "e")
			   ->where("e.socialNetworkIdentifiers IS NOT NULL")
			   ->getQuery()->getResult();

			foreach($datas as $data) {
				$d = $data->getSocialNetworkIdentifiers();
				
				if(!in_array(key($d["Blogger"]), array_values($urlId))) {
					
					if(!isset($d["Blogger"]["url"]))
						continue;
					
					$urlArray = explode(".", parse_url($d["Blogger"]["url"], PHP_URL_HOST));
					$v = ["Blogger" => [$urlId[reset($urlArray)] => $d["Blogger"]]];
					
					$data->setSocialNetworkIdentifiers($v);
					
					$this->em->persist($data);
				}
			}

			$this->em->flush();
		}

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