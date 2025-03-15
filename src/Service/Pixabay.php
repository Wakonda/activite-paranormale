<?php

	namespace App\Service;

	class Pixabay {
		private function getImageId(String $url): ?String {
			$urlArray = array_filter(explode("/", parse_url($url, PHP_URL_PATH)));
			$idArray = explode("-", end($urlArray));

			return end($idArray);
		}

		public function getImageInfos(String $url): array {
			$minWidth = 500;
			
			$id = $this->getImageId($url);

			$key = $_ENV["PIXABAY_KEY"];
			
			$url = "https://pixabay.com/api/?key={$key}&id={$id}&min_width={$minWidth}";

			$image = json_decode(@file_get_contents($url));

			if(empty($image))
				return [];
			
			if($image->totalHits == 0)
				return [];

			$infos = $image->hits[0];

			return ["url" => $infos->webformatURL,
					"user" => $infos->user,
					"license" => "Pixabay",
					"description" => ""];
		}
	}