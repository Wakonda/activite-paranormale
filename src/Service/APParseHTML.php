<?php
	namespace App\Service;

	use App\Service\APImgSize;
	
	class APParseHTML
	{
		public function saveImageFromURL($string, $path)
		{
			if(preg_replace("/(\r\n|\n|\r)/", "", $string) != "")
			{
				$doc = new \DomDocument();
				
				libxml_use_internal_errors(true);
				$doc->loadHTML(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'));
				libxml_use_internal_errors(false);
				
				$images = $doc->getElementsByTagName('img');

				foreach($images as $image)
				{
					$src = $image->getAttribute('src');
					
					if(preg_match("#^http://#", $src) OR preg_match("#^https://#", $src))
					{
						$url=@getimagesize($src);

						if(is_array($url))
						{
							$content = file_get_contents($src);
							
							if(empty($content))
								return $string;
							
							$nameFileArray = explode('/', strrev($src));
							$nameFile = uniqid().'_'.strrev($nameFileArray[0]);
							file_put_contents($path.$nameFile, $content);
							$image->setAttribute('src', '/'.$path.$nameFile);
						
							$apImgSize = new APImgSize();
							$fileInfo = $apImgSize->adaptImageSize(550, $path.$nameFile);
							$image->setAttribute('width', $fileInfo[0]);
							$image->setAttribute('height', $fileInfo[1]);
						}
						else
						{
							$image->parentNode->removeChild($image); 
						}
					}
				}
				$string = $this->removeHTMLWrapper($doc->saveHTML());
			}

			return $string;
		}

		/**
		 * @param string $string
		 */
		public function eraseVideo($string)
		{
			$tagsToRemove = ["iframe", "object", "script", "video"];
			
			foreach($tagsToRemove as $tag)
			{
				$string = preg_replace("/<".$tag."[^>]+\><\/".$tag.">/i", "", $string);
				$string = preg_replace("/<".$tag.".*?\/".$tag.">/i", "", $string);
			}

			return $string;
		}

		public function centerImageInHTML($string, $entity, $maxWidth = 550)
		{
			if(preg_replace("/(\r\n|\n|\r)/", "", $string) != "")
			{
				$doc = new \DomDocument();
				libxml_use_internal_errors(true);
				$doc->loadHTML(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'));
				libxml_use_internal_errors(false);
				
				$images = $doc->getElementsByTagName('img');

				foreach($images as $image)
				{
					if(method_exists($entity, "getAssetImagePath"))
					{
						if(!$image->hasAttribute('width'))
						{
							$fileName = preg_replace('/^.+[\\\\\\/]/', '', $image->getAttribute('src'));
							$assetPath = $entity->getAssetImagePath();
						
							if(!file_exists($assetPath.$fileName))
								continue;
						
							$imgsize = getimagesize($assetPath.$fileName);

							if($imgsize[0] > $maxWidth)
							{
								$eX = $maxWidth / $imgsize[0];

								$image->setAttribute("width", round(floatval($eX * $imgsize[0])));
								$image->setAttribute("height", round(floatval($eX * $imgsize[1])));
							}
						}
					}

					if(!empty($image->parentNode) and rtrim($image->parentNode->getAttribute("style"), ";") == "text-align: center")
						continue;
				
					$p = $doc->createElement("p");

					$image->parentNode->replaceChild($p, $image);
					$p->appendChild($image);
					$p->setAttribute('style', 'text-align: center');
				}

				$string = $this->removeHTMLWrapper($doc->saveHTML());
				$string = $doc->saveHTML();
				
				$config = array(
						   'show-body-only' => true,
						   );
				$tidy = new \tidy();
				$tidy->parseString($string, $config, 'utf8');
				$tidy->cleanRepair();

				return trim($tidy->value);
			}

			return "";
		}

		public function getVideoResponsive($html)
		{
			$doc = new \DOMDocument();
			$div = $doc->createElement('div');

			// use a helper to load the HTML into a string
			$iframeDOM = new \DOMDocument();
			libxml_use_internal_errors(true);
			$iframeDOM->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			libxml_use_internal_errors(false);
			
			$tag = $iframeDOM->childNodes->item(0)->tagName;

			if($tag == "blockquote")
				return $html;

			$iframe = $iframeDOM->getElementsByTagName($tag)->item(0);
			$iframe->setAttribute("style", "position: absolute; top: 0; left: 0; width: 100%; height: 100%;");
			
			$div->setAttribute("style", "position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden;");
			$div->appendChild($doc->importNode($iframeDOM->documentElement, true));
			
			$doc->appendChild($div);
			
			return $doc->saveHTML();
		}
		
		private function removeHTMLWrapper($html) {
			return preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $html);
		}
		
		private function deleteChildren($node) { 
			while (isset($node->firstChild)) { 
				$this->deleteChildren($node->firstChild); 
				$node->removeChild($node->firstChild); 
			} 
		}
		
		public function replacePathImgByFullURL($html, $baseURL)
		{
			$doc = new \DOMDocument();

			libxml_use_internal_errors(true);
			$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			libxml_use_internal_errors(false);
			
			$imgs = $doc->getElementsByTagName('img');
			
			foreach ($imgs as $img)
			{
				$old_src = $img->getAttribute('src');
				
				if(preg_match("/^(https?)?:?\/\//i", $old_src))
					$new_src_url = $old_src;
				else
					$new_src_url = $baseURL.$old_src;
				
				$img->setAttribute('src', $new_src_url);
			}
			return $doc->saveHTML();
		}
		
		public function replacePathLinksByFullURL($html, $baseURL)
		{
			$doc = new \DOMDocument();

			libxml_use_internal_errors(true);
			$doc->loadHTML(mb_convert_encoding("<div>".$html."</div>", 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			libxml_use_internal_errors(false);
			
			$links = $doc->getElementsByTagName('a');
		
			foreach ($links as $link)
			{
				$old_src = $link->getAttribute('href');

				if(!$this->is_absolute($old_src))
				{
					$new_src_url = $baseURL.$old_src;
					$link->setAttribute('href', $new_src_url);
				}
			}

			return $doc->saveHTML();
		}
		
		public function is_absolute($url)
		{
			$pattern = "/^(?:ftp|https?|feed):\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
			(?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
			(?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
			(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

			return (bool) preg_match($pattern, $url);
		}
	
		public function getContentURL($url, $proxy = null, $sanitizeUrl = true)
		{
			if($sanitizeUrl) {
				$urlArray = explode("/", $url);
				$urlArray[count($urlArray) - 1] = urlencode(end($urlArray));
				$url = implode("/", $urlArray);
				
				$proxyArray = explode(":", $proxy);
			}

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