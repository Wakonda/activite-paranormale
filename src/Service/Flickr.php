<?php

namespace App\Service;

class Flickr {
	private $apiKey = null;

	public function __construct() {
		$this->apiKey = $_ENV["FLICKR_API_KEY"];
	}

	public function getImageInfos($photoIdOrUrl) {
		$photoId = $photoIdOrUrl;

		if(filter_var($photoIdOrUrl, FILTER_VALIDATE_URL)) {
			$photoId = $this->getPhotoIdByUrl($photoIdOrUrl);
		}
		
		$baseUrl = 'https://api.flickr.com/services/rest/?';
		$method = 'flickr.photos.getInfo';
		$params = array(
			'api_key' => $this->apiKey,
			'method' => $method,
			'photo_id' => $photoId,
			'format' => 'json',
			'nojsoncallback' => 1
		);

		$requestUrl = $baseUrl . http_build_query($params);

		$ch = curl_init($requestUrl);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			echo 'cURL error: ' . curl_error($ch);
			exit;
		}

		curl_close($ch);

		$data = json_decode($response, true);

		if($data["stat"] == "fail")
			return null;
		
		$path = "https://live.staticflickr.com/{$data['photo']['server']}/{$data['photo']['id']}_{$data['photo']['secret']}.{$data['photo']['originalformat']}";

		$res = [
			"url" => "https://live.staticflickr.com/{$data['photo']['server']}/{$data['photo']['id']}_{$data['photo']['secret']}.{$data['photo']['originalformat']}",
			"title" => $data["photo"]["title"]["_content"],
			"description" => $data["photo"]["description"]["_content"],
			"user" => $data["photo"]["owner"]["realname"],
			"license" => $this->getLicense($data["photo"]["license"])
		];

		return $res;
	}
	
	public function getLicense($licenseId) {
		$baseUrl = 'https://api.flickr.com/services/rest/?';
		$method = 'flickr.photos.licenses.getInfo';
		$params = array(
			'api_key' => $this->apiKey,
			'method' => $method,
			'format' => 'json',
			'nojsoncallback' => 1
		);

		$requestUrl = $baseUrl . http_build_query($params);

		$ch = curl_init($requestUrl);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			echo 'cURL error: ' . curl_error($ch);
			exit;
		}

		curl_close($ch);

		$datas = json_decode($response, true);

		if($datas["stat"] == "fail")
			return null;

		return $this->getLicenseName($datas["licenses"]["license"][$licenseId]["name"]);
	}

	public function getLicenseName($titleLicense) {
		return [
			"All Rights Reserved" => "Copyright",
			"Attribution License" => "CC BY 2.0",
			"Attribution-NoDerivs License" => "CC BY-ND 2.0",
			"Attribution-NonCommercial-NoDerivs" => "CC BY-NC-ND 2.0",
			"Attribution-NonCommercial License" => "CC BY-NC 2.0",
			"Attribution-NonCommercial-ShareAlike License" => "CC BY-NC-SA 2.0",
			"Attribution-ShareAlike License" => "CC BY-SA 2.0",
			"No known copyright restrictions" => "Copyleft",
			"United States Government Work" => "Copyright",
			"Public Domain Dedication (CC0)" => "CC0",
			"Public Domain Mark" => "PDM 1.0",
		][$titleLicense];
	}

	private function getPhotoIdByUrl(string $url) {
		list($type, $userId, $photoId) = array_values(array_filter(explode("/", parse_url($url, PHP_URL_PATH))));
		
		return $photoId;
	}
}