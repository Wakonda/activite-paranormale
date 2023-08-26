<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\FileManagement;
use App\Entity\Grimoire;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\SurThemeGrimoire;

#[AsCommand(
   name: 'app:parse-blogger-xml'
)]
class ParseBloggerXMLCommand extends Command
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
		$output->writeln("Start Event migration");

		$conn = $this->em->getConnection();

		$blogId = "4192778394306065291";

		$dom = new \DOMDocument();
		$dom->loadXML(file_get_contents((new Grimoire())->getTmpUploadRootDir()."blog-09-25-2022.xml"));

		$entries = $dom->getElementsByTagName('entry');

		$categoriesArray = [];

		$i = 0;

		foreach ($entries as $entry) {	
			$categories = $entry->getElementsByTagName("category");

			if(!$this->isPost($categories))
				continue;
			
			if(strpos($entry->getElementsByTagName("id")->item(0)->nodeValue, "tag:blogger.com,1999:blog-${blogId}.post") !== false) {
				$idsArray = explode("-", $entry->getElementsByTagName("id")->item(0)->nodeValue);
				
				$entity = $this->em->getRepository(Grimoire::class)->getGrimoireBySocialNetworkIdentifiers("Blogger", end($idsArray));
				// dd($entity);
				if(empty($entity))
					$entity = new Grimoire();
				
				$this->em->persist($entity);
				
				$language = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "fr"]);
				
				$entity->setLanguage($language);
				
				$entity->setTitle($entry->getElementsByTagName("title")->item(0)->nodeValue);
				$entity->setWritingDate(new \DateTime($entry->getElementsByTagName("published")->item(0)->nodeValue));
				$entity->setPublicationDate(new \DateTime($entry->getElementsByTagName("updated")->item(0)->nodeValue));
				
				$links = $entry->getElementsByTagName("link");
				
				$sourceArray = [[
					"author" => null,
					"url" => $links->item($links->length - 1)->getAttribute("href"),
					"type" => "url",
				]];
				
				$entity->setSource(json_encode($sourceArray));
				
				$imgData = [];
				
				$domContent = new \DOMDocument();
				
				libxml_use_internal_errors(true);
				$domContent->loadHTML(mb_convert_encoding($entry->getElementsByTagName("content")->item(0)->nodeValue, 'HTML-ENTITIES', 'UTF-8'));
				libxml_clear_errors();
				
				$images = $domContent->getElementsByTagName("img");
				
				if(!empty($images->item(0))) {
					$imgArray = explode("/", $images->item(0)->getAttribute("src"));
					$imgData = ["title" => $imgArray[count($imgArray) - 1], "content" => file_get_contents($images->item(0)->getAttribute("src"))];
				}
				
				$imgs = array();
				foreach($images as $img) {
					$imgs[] = $img;
				}
				foreach($imgs as $img) {
					$img->parentNode->removeChild($img);
				}
				
				$spans = $domContent->getElementsByTagName("span");
				
				foreach($spans as $span) {
					$span->removeAttribute("style");
				}
				
				$divs = $domContent->getElementsByTagName("div");
				
				foreach($divs as $div) {
					$div->removeAttribute("style");
					$div->removeAttribute("align");
				}
				
				foreach($divs as $div) {
					if($div->getAttribute("class") == "separator")
						$div->parentNode->removeChild($div);
				}
				
				$brs = $domContent->getElementsByTagName("br");
				
				foreach($brs as $br) {
					$br->parentNode->removeChild($br);
				}
				
				$html = $domContent->saveHTML();
		
				$html = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $html);

				$html = preg_replace('/(<!--)([\s\S]*?)-->/', '', $html);
				$html = preg_replace('/<(\w+)\b(?:\s+[\w\-.:]+(?:\s*=\s*(?:"[^"]*"|"[^"]*"|[\w\-.:]+))?)*\s*\/?>\s*<\/\1\s*>/', '', $html);
				$html = preg_replace('/^(<br\s*\/?>)*|(<br\s*\/?>)*$/i', '', $html);

				$entity->setText($html);
				
				$currentCategories = [
				  'Amour' => 'love',
				  'Argent' => 'job',
				  'Bonheur' => 'happiness',
				  'Chance' => 'job',
				  'Couple / Famille' => 'family',
				  'Don' => 'ability',
				  'Exorcisme' => 'exorcism',
				  'Guérison' => 'healing',
				  'Prière' => 'faith',
				  'Prière à Marie' => 'faith',
				  'Travail' => 'job',
				  'Défunt / mort' => 'death',
				  'Protection' => 'protection'
				];
				
				$categoryArray = [];
				
				foreach($categories as $category) {
					$term = $category->getAttribute("term");
					if(!filter_var($term, FILTER_VALIDATE_URL)) {
						$c = $term;
					}
					
					$categoryArray[] = $term;
				}
				// dump($c);
				$stg = $this->em->getRepository(SurThemeGrimoire::class)->findOneBy(["internationalName" => $currentCategories[$c].'Catholicism', "language" => $language]);
				
				if(!empty($stg))
					$entity->setSurTheme($stg);
				
				$state = $this->em->getRepository(State::class)->findOneBy(["internationalName" => "Validate", "language" => $language]);
				
				$entity->setState($state);
				$entity->setPseudoUsed("Wakonda");
				$entity->setArchive(false);

				if(!empty($imgData)) {
					$illustration = $entity->getIllustration();
					
					if(empty($illustration))
						$illustration = new FileManagement();
					
					$illustration->setTitleFile($imgData["title"]);
					$illustration->setRealNameFile($imgData["title"]);
					$illustration->setLicense("Pixabay");
					$illustration->setUrlSource("https://pixabay.com/");
					$illustration->setExtensionFile(pathinfo($imgData["title"], PATHINFO_EXTENSION));
					
					file_put_contents($entity->getTmpUploadRootDir().$imgData["title"], $imgData["content"]);
					
					$entity->setIllustration($illustration);
					
					$this->em->persist($illustration);
				}

				$socialNetworkIdentifiers = [];
				$socialNetworkIdentifiers["Blogger"] = [
					"id" => end($idsArray),
					"url" => $links->item($links->length - 1)->getAttribute("href"),
					"labels" => $categoryArray,
				];
				
				$entity->setSocialNetworkIdentifiers($socialNetworkIdentifiers);
			}
		}
		
		$this->em->flush();

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