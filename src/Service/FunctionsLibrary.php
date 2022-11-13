<?php

	namespace App\Service;
	
	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\Licence;

	class FunctionsLibrary
	{
		private $em;
		
		public function __construct(EntityManagerInterface $em = null)
		{
			$this->em = $em;
		}

		public function isUrl($string)
		{
			$regex = "((https?|ftp)\:\/\/)?"; // SCHEME 
			$regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass 
			$regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP 
			$regex .= "(\:[0-9]{2,5})?"; // Port 
			$regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path 
			$regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query 
			$regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor 

			if(preg_match("/^$regex$/", $string))
				return true;
		   
			return false;
		}
		
		public function sourceString($sourceJSON, string $locale, Array $classes = []): ?String {
			$datas = json_decode($sourceJSON, true);

			if(empty($datas))
				return null;

			$res = [];
			
			foreach($datas as $data) {
				$subRes = [];
				switch($data["type"]) {
					case "url":
						$wd = null;
						
						if(!empty($this->em))
							$wd = $this->em->getRepository("\App\Entity\WebDirectory")->getWebdirectoryByUrl($data["url"], $locale);
						$licence = null;
						
						if(!empty($wd) and !empty($wd->getLicence()))
							$licence = "<a href='".$wd->getLicence()->getLink()."' target='_blank'>".$wd->getLicence()->getTitle()."</a>";
						
						if(!empty($data["title"]))
							$subRes[] = '<i><a href="'.$data["url"].'">'.$data["title"].'</a></i> - <b>'.parse_url($data["url"], PHP_URL_HOST).'</b>'.(!empty($licence) ? " - ".$licence : "");
						else
							$subRes[] = '<a href="'.$data["url"].'">'.$this->cleanUrl($data["url"]).'</a>'.(!empty($licence) ? " - ".$licence : "");
						
						if(!empty($data["author"]))
							$subRes[] = $data["author"];
						
						if(!empty($data["date"]))
							$subRes[] = (new APDate())->doDate($locale, new \DateTime($data["date"]));
						break;
					case "work":
						if(!empty($data["title"]))
							$subRes[] = '<i>'.$data["title"].'</i>';
						if(!empty($data["author"]))
							$subRes[] = $data["author"];
						if(!empty($data["isbn10"]) or !empty($data["isbn13"]))
							$subRes[] = "[".(!empty($data["isbn13"]) ? "ISBN-13: ".$data["isbn13"] : "ISBN-10: ".$data["isbn10"])."]";
						if(!empty($data["date"]))
							$subRes[] = (new APDate())->doDate($locale, new \DateTime($data["date"]));
						break;
					case "article":
						if(!empty($data["title"]))
							$subRes[] = empty($data["url"]) ? '<i>'.$data["title"].'</i>' : '<i><a href="'.$data["url"].'">'.$data["title"].'</a></i>';
						if(!empty($data["periodical"]))
							$subRes[] = '<u>'.$data["periodical"].'</u>';
						if(!empty($data["author"]))
							$subRes[] = $data["author"];
						if(!empty($data["date"]))
							$subRes[] = (new APDate())->doDate($locale, new \DateTime($data["date"]));
						break;
					default:
						break;
				}
				
				$res[] = "<li>".implode(", ", $subRes)."</li>";
			}

			return "<ul".(!empty($classes) ? " class='".implode(" ", $classes)."'" : "").">".implode("", $res)."</ul>";
		}
		
		public function cleanUrl(String $url): ?String
		{
			if((preg_match("#^http://#", $url) == 0) && (preg_match("#^https://#", $url) == 0))
				$url = "http://".$url;

			return parse_url($url)["host"];
		}
	}