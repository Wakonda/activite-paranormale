<?php
	namespace App\Service;
	
	use Symfony\Component\DependencyInjection\ContainerInterface;
	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\Page;
	
	class RSSFeed
	{
		private $container;
		private $em;
		
		public function __construct(ContainerInterface $container, EntityManagerInterface $em)
		{
			$this->container = $container;
			$this->em = $em;
		}

		private function getResultsToSyndication($theme, $length, $language)
		{
			switch($theme)
			{
				case "document":
					$entityPath = "App\Entity\Document";
					break;
				case "photo":
					$entityPath = "App\Entity\Photo";
					break;
				case "testimony":
					$entityPath = "App\Entity\Testimony";
					break;
				case "video":
					$entityPath = "App\Entity\Video";
					break;
				default:
					$entityPath = "App\Entity\News";
					break;
			}

			$languageArray = explode(',', $language);

			if(!empty(array_filter($languageArray)))
			{
				foreach($languageArray as $language)
				{
					$whereInLanguageArray[] = ":".$language;
				}
			}
			else {
				$whereInLanguageArray = [':fr'];
				$languageArray = ['fr'];
			}

			$query = $this->em->createQuery('SELECT u FROM '.$entityPath.' u INNER JOIN u.language l WHERE l.abbreviation IN ('.implode(',', $whereInLanguageArray).') ORDER BY u.publicationDate');

			foreach($languageArray as $language)
			{
				$query->setParameter($language, $language);
			}
			$query->setMaxResults($length);

			return $query->getResult();
		}

		private function initFeed()
		{
			return new \DOMDocument( "1.0", "ISO-8859-15" );
		}

		private function headerFeed($xml)
		{
			$rss = $xml->createElement("rss");
			$rss->setAttribute("version", "2.0");
			$rss->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");
			
			$xml->appendChild($rss);
			
			$channel = $xml->createElement("channel");
			$rss->appendChild($channel);

			$title = $xml->createElement("title");
			$channel->appendChild($title);
			$titleContent = $xml->createTextNode(utf8_encode("Activité-Paranormale"));
			$title->appendChild($titleContent);
			
			$description = $xml->createElement("description");
			$channel->appendChild($description);
			$descriptionContent = $xml->createTextNode(trim(strip_tags($this->em->getRepository(Page::class)->getPageByLanguageAndType($this->container->get('request_stack')->getCurrentRequest()->getLocale(), "descriptionMetaTag")->getText())));
			$description->appendChild($descriptionContent);

			$link = $xml->createElement("link");
			$channel->appendChild($link);
			$linkContent = $xml->createTextNode(utf8_encode("http://activite-paranormale.net"));
			$link->appendChild($linkContent);
			
			$ttl = $xml->createElement("ttl");
			$channel->appendChild($ttl);
			$ttlContent = $xml->createTextNode("60");
			$ttl->appendChild($ttlContent);

			$pubDate = $xml->createElement("pubDate");
			$channel->appendChild($pubDate);
			
			$date = new \DateTime();
			$date = $date->format("D, d M Y H:i:s O");
			$pubDateContent = $xml->createTextNode($date);
			$pubDate->appendChild($pubDateContent);
			
			return $xml;
		}

		private function contentFeed($xml, $theme, $length, $language)
		{
			$entities = $this->getResultsToSyndication($theme, $length, $language);
			
			foreach($entities as $entity)
			{
				$item = $xml->createElement("item");
				$channel = $xml->getElementsByTagName('channel');
				$channel->item(0)->appendChild($item);
				
				$title = $xml->createElement("title");
				$item->appendChild($title);
				$titleContent = $xml->createTextNode($entity->getTitle());
				$title->appendChild($titleContent);

				$description = $xml->createElement("description");
				$item->appendChild($description);
				$text = (method_exists($entity, "getAbstractText")) ? $entity->getAbstractText() : $entity->getText();
				$descriptionContent = $xml->createTextNode($text);
				$description->appendChild($descriptionContent);
				
				$pubDate = $xml->createElement("pubDate");
				$item->appendChild($pubDate);
				$pubDateContent = $xml->createTextNode($entity->getPublicationDate()->format("D, d M Y H:i:s O"));
				$pubDate->appendChild($pubDateContent);
			}
			
			return $xml;
		}

		public function generateFeed($query)
		{
			$theme = $query->get('theme');
			$language = $query->get('language');
			$length = $query->get('length');

			$xml = $this->initFeed();
			$xml = $this->headerFeed($xml);
			$xml = $this->contentFeed($xml, $theme, $length, $language);
			return $xml->saveXML();
		}
	}