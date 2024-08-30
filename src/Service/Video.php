<?php

namespace App\Service;

class Video {
	const LOCALE_PLATFORM = "AP";
	const DAILYMOTION_PLATFORM = "Dailymotion";
	const FACEBOOK_PLATFORM = "Facebook";
	const INSTAGRAM_PLATFORM = "Instagram";
	const RUTUBE_PLATFORM = "Rutube";
	const TWITTER_PLATFORM = "Twitter";
	const YOUTUBE_PLATFORM = "Youtube";
	const OTHER_PLATFORM = "Other";

	public function __construct(public string $embeddedCode) {}

	public function getThumbnailVideo() {
		$code = $this->embeddedCode;
		$platform = $this->getPlatformByCode($code);
		$pattern = '/<[^>]*>/';

		if ($platform == strtolower(self::YOUTUBE_PLATFORM)) {
			$pattern = '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/';
			if (preg_match($pattern, $code, $matches)) {
				$videoId = $matches[1];
				
				foreach(["maxresdefault", "hqdefault", "mqdefault", "sddefault"] as $format) {
					$url = "https://img.youtube.com/vi/{$videoId}/$format.jpg";
					if(substr(get_headers($url, 1)[0], 9, 3) != "404")
						return $url;
				}

				return null;
			} else
				return null;
		} elseif ($platform == strtolower(self::DAILYMOTION_PLATFORM)) {
			$dom = new \DOMDocument();
			$dom->loadHTML($code);
			$iframe = $dom->getElementsByTagName('iframe')->item(0);

			if ($iframe) {
				$src = $iframe->getAttribute('src');
				$src = parse_url($src);
				$videoId = substr($src["path"], strrpos($src["path"], '/') + 1);
				
				if($videoId == "player.html") {
					$videoId = str_replace("video=", "", $src["query"]);
				}

				return "https://www.dailymotion.com/thumbnail/video/{$videoId}";
			}
		} elseif($platform == strtolower(self::RUTUBE_PLATFORM)) {
			$pattern = '/src="https:\/\/rutube\.ru\/play\/embed\/([^"]+)"/';
			if (preg_match($pattern, $code, $matches)) {
				$videoId = $matches[1];

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL,"https://rutube.ru/api/video/{$videoId}/thumbnail");
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

				$json = curl_exec($curl);
				$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

				curl_close($curl);

				if($httpCode == 200) {
					$json = json_decode($json);
					return $json->url;
				}
			}
		} else {
			return null;
		}

		return null;
	}

	public function getPlatformByCode() {
		$code = $this->embeddedCode;
		$platform = null;
		$pattern = '/<[^>]*>/';

		if (preg_match($pattern, $code)) {
			$doc = new \DOMDocument();
			$doc->loadHTML($code);

			$iframe = $doc->getElementsByTagName('iframe')->item(0);

			if(!empty($iframe)) {
				$srcAttribute = $iframe->getAttribute('src');

				if (strpos($srcAttribute, 'youtube.com') !== false) {
					return strtolower(self::YOUTUBE_PLATFORM);
				} elseif (strpos($srcAttribute, 'dailymotion.com') !== false) {
					return strtolower(self::DAILYMOTION_PLATFORM);
				} elseif(strpos($srcAttribute, 'rutube.ru') !== false) {
					return strtolower(self::RUTUBE_PLATFORM);
				}
			}

			if (str_contains($code, "twitter"))
				return strtolower(self::TWITTER_PLATFORM);
		}

		return $platform;
	}

	public function getURLByCode() {
		$code = $this->embeddedCode;
		$platform = null;
		$pattern = '/<[^>]*>/';

		if (preg_match($pattern, $code)) {
			$doc = new \DOMDocument();
			$doc->loadHTML($code);

			$iframe = $doc->getElementsByTagName('iframe')->item(0);

			if(empty($iframe))
				return null;

			return $iframe->getAttribute('src');
		}

		return $platform;
	}
}