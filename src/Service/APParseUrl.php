<?php
	namespace App\Service;
	
	class APParseUrl
	{
		/**
		 * @param string $url
		 */
		public function cleanURLLink($url)
		{
			if((preg_match("#^http://#", $url) == 0) && (preg_match("#^https://#", $url) == 0))
			{
				$url = "http://".$url;
			}
			$p = parse_url($url);
			
			return $p["host"];
		}
	}