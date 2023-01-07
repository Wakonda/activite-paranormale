<?php
	namespace App\Service;

	class Wikimonde {
		public $url;

		public function setUrl(String $url)
		{
			$this->url = $url;
		}

		public function getContentBySections(Array $sectionIndexArray): String {
			if(empty($sectionIndexArray))
				return "";
			
			$content = [];
			
			$dom = new \DomDocument();
			$dom->loadHTML($this->getContentURL($this->url), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
			
			$xpath = new \DOMXPath($dom);
			foreach($xpath->query('//div[contains(attribute::class, "bandeau-container")]') as $e )
				$e->parentNode->removeChild($e);
			foreach($xpath->query('//p[contains(attribute::class, "mw-empty-elt")]') as $e )
				$e->parentNode->removeChild($e);
			foreach($xpath->query('//sup') as $e )
				$e->parentNode->removeChild($e);
			
			$content = $dom->getElementById('mw-content-text');
			
			$res = [];
			$title = 0;

			foreach($content->getElementsByTagName('*') as $element ) {
				if($element->tagName == "h2") {
					$title = $element->nodeValue;
				}
				if($element->tagName == "p") {
					$res[$title][] = strip_tags($dom->saveHTML($element),'<b><i><p>');
				}
			}

			foreach(array_keys($res) as $section) {
				if(!in_array($section, $sectionIndexArray))
					unset($res[$section]);
			}
			
			$contentArray = [];
			
			foreach($res as $title => $r) {
				if(!empty($title))
					$contentArray[] = "<h3>${title}</h3>";

				$contentArray[] = implode("", $r);
			}

			return implode("", $contentArray);
		}
		
		public function getSections(): Array {
			$res = [];

			$dom = new \DomDocument();

			$dom->loadHTML($this->getContentURL($this->url), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);

			$content = $dom->getElementById('mw-content-text');
			$i = 0;
			
			foreach ($content->getElementsByTagName('h2') as $item)
				$res[$item->nodeValue] = $item->nodeValue;

			return $res;
		}

		public function getContentURL($url, $proxy = null)
		{
			if(!empty($proxy))
			$proxyArray = explode(":", $proxy);
			$curl = curl_init();
			$timeout = 30;
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_REFERER, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.125 Safari/533.4");
			
			if(!empty($proxy))
			{
				curl_setopt($curl, CURLOPT_PROXY, $proxyArray[0]);
				curl_setopt($curl, CURLOPT_PROXYPORT, $proxyArray[1]);
			}

			$str = curl_exec($curl);
			curl_close($curl);
			
			return $str;
		}
	}