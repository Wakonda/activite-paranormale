<?php
	namespace App\Service;

	class Wikipedia {
		public $locale;
		public $page;
		public $sectionName;
		public $url;
		private $keepTitle = false;
		
		public function setUrl(String $url)
		{
			$urlParse = parse_url($url);

			$this->locale = explode(".", $urlParse["host"])[0];
			$this->page = array_reverse(explode("/", $urlParse["path"]))[0];
		}
		
		public function getContentBySection(Int $sectionIndex = null): String {
			$url = "https://{$this->locale}.wikipedia.org/w/api.php?action=parse&format=json&page={$this->page}&prop=text".($sectionIndex !== null ? "&section=${sectionIndex}" : "");

			$content = json_decode(file_get_contents($url), true);

			return $this->wikiTextToHTML($content["parse"]["text"]["*"]);
		}
		
		public function getContentBySections(Array $sectionIndexArray): String {
			$content = [];

			if(count($sectionIndexArray) > 1) {
				$this->keepTitle = true;
				foreach($sectionIndexArray as $section) {
					$content[] = $this->getContentBySection($section);
				}
			} elseif(count($sectionIndexArray) == 1)
				$content[] = $this->getContentBySection($sectionIndexArray[0]);
			else {
				$this->keepTitle = true;
				$content[] = $this->getContentBySection();
			}

			return implode("", $content);
		}
		
		public function getSections(Int $toclevel = 1): Array {
			$sections = json_decode(file_get_contents("https://".$this->locale.".wikipedia.org/w/api.php?action=parse&format=json&page=".$this->page."&prop=sections&disabletoc=1"));
			
			$res = [];
			
			foreach($sections->parse->sections as $section) {
				if($toclevel == $section->toclevel or empty($toclevel))
					$res[$section->line] = $section->index;
			}
			
			return $res;
		}
		
		public function getAllSections(): Array {
			$sections = json_decode(file_get_contents("https://".$this->locale.".wikipedia.org/w/api.php?action=parse&format=json&page=".$this->page."&prop=sections&disabletoc=1"));
			
			return $sections->parse->sections;
			$res = [];
			
			foreach($sections->parse->sections as $section) {
				$res[$section->line] = $section->index;
			}
			
			return $res;
		}
		
		private function wikiTextToHTML(String $html): String {
			$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

			$dom = new \DOMDocument();
			libxml_use_internal_errors(true);
			$dom->loadHTML("<html>".$html."</html>", LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED );

			$xpath = new \DOMXPath($dom);
			foreach($xpath->query('//div[contains(attribute::class, "thumb")]') as $e )
				$e->parentNode->removeChild($e);

			foreach($xpath->query('//table[contains(attribute::class, "sidebar")]') as $e )
				$e->parentNode->removeChild($e);

			foreach($xpath->query('//table[contains(@class, "toccolours")]') as $e )
				$e->parentNode->removeChild($e);

			foreach($xpath->query('//a[contains(@class, "extiw")]') as $e )
				$e->parentNode->removeChild($e);

			foreach($xpath->query('//div[contains(attribute::class, "listen")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//sup[contains(attribute::class, "need_ref_tag")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//ul[contains(attribute::class, "gallery")][contains(attribute::class, "mw-gallery-packed")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//ol[contains(attribute::class, "references")]') as $e ) {
				$e->parentNode->removeChild($e);
			} 
			foreach($xpath->query('//table[contains(attribute::class, "biography")][contains(attribute::class, "vcard")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//table[contains(attribute::class, "plainlinks")][contains(attribute::class, "ambox")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//div[contains(attribute::class, "mw-references-wrap")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//sup[contains(attribute::class, "reference")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//span[contains(attribute::class, "mw-editsection")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//div[contains(attribute::class, "metadata")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//sup[contains(attribute::class, "prononciation")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//div[contains(attribute::class, "infobox_v3")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//div[contains(attribute::id, "toc")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//div[contains(attribute::class, "navbox-container")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//table[contains(attribute::class, "ambox-content")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//div[contains(attribute::class, "hatnote")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//div[contains(attribute::class, "plainlinks")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//sup[contains(attribute::class, "noprint")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//table[contains(attribute::class, "infobox_v2")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//table[contains(attribute::class, "infobox")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//span[contains(attribute::class, "mw-ext-cite-error")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//style') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//link') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//div[contains(attribute::class, "searchaux")]') as $e ) {
				$e->parentNode->removeChild($e);
			}
			foreach($xpath->query('//span[contains(attribute::class, "haudio")]') as $e ) {
				$e->parentNode->removeChild($e);
			}

			if(!$this->keepTitle) {
				foreach($xpath->query('//h2') as $e ) {
					$e->parentNode->removeChild($e);
				}
			}
			foreach ($xpath->query('//comment()') as $comment) {
				$comment->parentNode->removeChild($comment);
			}

			$html = $dom->saveHTML();

			$html = preg_replace('~<(?:!DOCTYPE|/?(?:html|body|head))[^>]*>\s*~i', '', $html);
			
			$htmlArray = [];
			
			foreach($xpath->query('//div[@class="mw-parser-output"]/*') as $e ) {
				$htmlArray[] = $dom->saveHTML($e);
			}

			$html = implode("", $htmlArray);

			$html = preg_replace_callback("/(<a.*?>).*?(<\/a>)/", 
					function ($matches) {
						return strip_tags($matches[0]);
					},
					$html);
				
			foreach($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
				$html = str_replace($heading->ownerDocument->saveHTML($heading), "<{$heading->tagName}>".trim(strip_tags($dom->saveHTML($heading)))."</{$heading->tagName}>", $html);
			}
					
			$html = preg_replace_callback("(<h([1-6])>(.*?)</h[1-6]>)", 
			function ($matches) {
				return '<h'.($matches[1] + 1).'>'.$matches[2].'</h'.($matches[1] + 1).'>';
			},
			$html);

			$html = preg_replace_callback("#<p[^>]*>(\s|&nbsp;|</?\s?br\s?/?>)*</?p>#", 
			function ($matches) {
				return "";
			},
			$html);
			
			$html = preg_replace ("!\s++!u", ' ', $html);
	
			return $html;
		}
		
		private function replaceLink(String $str): String {
			return preg_replace_callback("/\[\[[^\]]*\]\]/", 
			function ($matches) {
				return array_reverse(explode("|", str_replace(["[[", "]]"], "", $matches[0])))[0];
			},
			$str);
		}

		private function removeTitle(String $str): String {
			$str = preg_replace_callback("/^== (.+?) ==$/m", 
			function ($matches) {
				return "";
			},
			$str);
		
			return trim($str);
		}
		
		private function replaceBracket(String $str): String {
			return preg_replace("/(\{([^{}]|(?1))*\})/", "", $str);
		}
	}