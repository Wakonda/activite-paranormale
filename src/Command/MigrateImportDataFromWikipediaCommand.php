<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ausi\SlugGenerator\SlugGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;

use App\Entity\UsefulLink;
use App\Entity\Language;
use App\Entity\Artist;
use App\Entity\FileManagement;
use App\Entity\Country;
use App\Entity\MusicGenre;
use App\Service\Wikipedia;
use App\Service\Wikidata;

// Example: php bin/console app:migrate-import-wikipedia --locale=en --url=https://en.wikipedia.org/wiki/List_of_symphonic_metal_bands --theme=1

#[AsCommand(
   name: 'app:migrate-import-wikipedia'
)]
class MigrateImportDataFromWikipediaCommand extends Command
{
    private $em;
    private $wikipedia;
    private $wikidata;

    public function __construct(EntityManagerInterface $em, Wikipedia $wikipedia, Wikidata $wikidata)
    {
		parent::__construct();
        $this->em = $em;
        $this->wikipedia = $wikipedia;
        $this->wikidata = $wikidata;
    }

    protected function configure()
    {
        $this
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale?')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'URL?')
            ->addOption('theme', null, InputOption::VALUE_OPTIONAL, 'Theme/Genre?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start Wikipedia data migration");

		$localeURL = explode(".", parse_url($input->getOption('url'), PHP_URL_HOST))[0];
		$html = file_get_contents($input->getOption('url'));

		libxml_use_internal_errors(true);

		$dom = new \DOMDocument();

		$dom->loadHTML($html);

		$xpath = new \DOMXPath($dom);

		$regex = '/[^a-zA-Z0-9_-]+/';;

		$locale = $input->getOption('locale');
		$language = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => $locale]);
		
		$datas = [];

		foreach($xpath->query("//div[@class='div-col']/ul/li/a") as $node) {
			$titleArray = explode("/", $node->getAttribute("href"));
			$title = end($titleArray);

			$code = $this->wikidata->getWikidataId($title, $localeURL);

			if(empty($code) or !empty($this->em->getRepository(Artist::class)->findOneBy(["wikidata" => $code, "language" => $language])))
				continue;

			$data = $this->wikidata->getArtistDatas($code, $locale);
			
			if(empty($data["url"]))
				continue;

			$this->wikipedia->setUrl($data["url"]);

			$data["biography"] = $this->wikipedia->getContentBySections(["0"]);
			$data["wikidata"] = $code;
			
			$datas[] = $data;
		}

		$genre = $this->em->getRepository(MusicGenre::class)->findOneBy(["id" => $input->getOption('theme')]);
		
		foreach($datas as $data) {
			$artist = new Artist();
			
			$artist->setLanguage($language);
			$artist->setTitle($data["title"]);
			$artist->setBiography($data["biography"]);
			$artist->setWikidata($data["wikidata"]);
			$artist->setWebsite($data["links"]);
			
			if(isset($data["identifiers"]))
				$artist->setIdentifiers(json_encode($data["identifiers"]));
			
			$artist->setGenre($genre);
			
			if(isset($data["origin"]))
				$country = $this->em->getRepository(Country::class)->find($data["origin"]["country"]["id"]);
			
			$artist->setCountry($country);

			$generator = new SlugGenerator;
			$in = $generator->generate($data["title"]).uniqid();
			
			$current = $this->em->getRepository(Artist::class)->findBy(["wikidata" => $data["wikidata"]]);

			$artist->setInternationalName($in);

			if(!empty($current))
				$artist->setInternationalName($current[0]->getInternationalName());

			$sourceArray = [[
				"author" => null,
				"url" => $data["url"],
				"type" => "url",
			]];
			
			$artist->setSource(json_encode($sourceArray));
			
			$this->em->persist($artist);

			if(!empty($data["image"]) and !empty($data["image"]["url"])) {
				$filename = basename($data["image"]["url"]);
				$explodeFilename = explode(".", strrev($filename), 2);
				$NNFile = preg_replace($regex, "-", strrev($explodeFilename[1]));
				$ExtFile = strrev($explodeFilename[0]);
				$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;

				$parser = new \App\Service\APParseHTML();
				$html = $parser->getContentURL($data["image"]["url"]);

				file_put_contents($artist->getTmpUploadRootDir().$NewNameFile, $html);
				
				$illustration = new FileManagement();
				$illustration->setTitleFile($NewNameFile);
				$illustration->setRealNameFile($NewNameFile);
				$illustration->setCaption($data["image"]["description"]);
				$illustration->setLicense($data["image"]["license"]);
				$illustration->setAuthor($data["image"]["user"]);
				$illustration->setUrlSource($data["image"]["source"]);
				$illustration->setExtensionFile(pathinfo($filename, PATHINFO_EXTENSION));
				
				$artist->setIllustration($illustration);
				
				$this->em->persist($illustration);
			}
		}

		$this->em->flush();

        return 0;
    }
}
