<?php

namespace App\Service;

class Friendica {
	private $FRIENDICA_ACCESS_TOKEN = null;
	private $FRIENDICA_URL = null;
	private $FRIENDICA_USERNAME = null;
	private $FRIENDICA_PASSWORD = null;
	
	// Check if API exists for a Friendica instance
	public function checkAPI(string $locale) {
		$this->setLanguage($locale);
		$instance = $this->FRIENDICA_URL;

		$url = "https://$instance/api/statusnet/version";
		
		$status = null;
		$data = null;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			$status = "error";
			$data = curl_error($ch);
		} else {
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			if ($httpCode == 200) {
				$status = "success";
				$data = $response;
			} else {
				$status = "error";
				$data = $response;
			}
		}

		curl_close($ch);
		
		return ["status" => $status, "data" => $data];
	}

	// Get Client Id and Client Secret
	public function getClientIdAndClientSecret(string $locale) {
		$this->setLanguage($locale);
		$instance = $this->FRIENDICA_URL;
		
		$instance = "https://$instance";

		$data = [
			'client_name' => 'ActiviteParanormale',
			'redirect_uris' => 'urn:ietf:wg:oauth:2.0:oob',
			'scopes' => 'read write follow',
			'website' => ''
		];

		$ch = curl_init($instance . '/api/v1/apps');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$status = null;
		$clientId = null;
		$clientSecret = null;
		$urlCode = null;

		if ($httpCode == 200) {
			$app = json_decode($response, true);
			
			$status = "success";
			$clientId = $app['client_id'];
			$clientSecret = $app['client_secret'];
			
			// Pour récupérer le code, il suffit de cliquer sur ce lien : https://ton-instance-friendica.com/oauth/authorize?client_id=TON_CLIENT_ID&scope=read+write&response_type=code&redirect_uri=urn:ietf:wg:oauth:2.0:oob
			$urlCode = "$instance/oauth/authorize?client_id=$clientId&scope=read+write&response_type=code&redirect_uri=urn:ietf:wg:oauth:2.0:oob";
		} else {
			$status = "error";
		}

		return ["status" => $status, "clientId" => $clientId, "clientSecret" => $clientSecret, "urlCode" => $urlCode];
	}

	public function getAccessToken(string $authorizationCode, string $clientId, string $clientSecret, string $locale) {
		$this->setLanguage($locale);
		$instance = $this->FRIENDICA_URL;
		$instance = "https://$instance";

		$ch = curl_init($instance . '/oauth/token');

		$data = [
			'client_id'     => $clientId,
			'client_secret' => $clientSecret,
			'code'          => $authorizationCode,
			'grant_type'    => 'authorization_code',
			'redirect_uri'  => 'urn:ietf:wg:oauth:2.0:oob'
		];

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		$response = curl_exec($ch);
		$json = json_decode($response, true);
		
		$status = null;
		$data = null;

		if (isset($json['access_token'])) {
			$status = "success";
			$data = $json['access_token'];
		} else {
			$status = "error";
			$data = $response;
		}

		curl_close($ch);

		return ["status" => $status, "data" => $data];
	}

	public function postMessage(string $url, string $message, string $locale) {
		$this->setLanguage($locale);
		$instance = $this->FRIENDICA_URL;
		$instance = "https://$instance";
		$accessToken = $this->FRIENDICA_ACCESS_TOKEN;
		
		$data = $this->parseHTML($url);
		$photo = !empty($i = $data["image"]) ? "<br><br>[img=$i][/img]" : "";

		$data_post = [
			'status' => $message.'<br><br>[url='.$url.'][/url]'.$photo
		];
// dd($data_post);
		$ch = curl_init($instance . '/api/statuses/update');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_post));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer ' . $accessToken
		]);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode == 200 || $httpCode == 201) {
			$status = "success";
			$result = json_decode($response, true);
			$data = "ID: ".$result['id']." / "."URL: " . ($result['url'] ?? 'N/A');
		} else {
			$status = "error";
			$data = $response;
		}

		return ["status" => $status, "data" => $data];
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "fr":
				$this->FRIENDICA_ACCESS_TOKEN = $_ENV["FRIENDICA_FR_ACCESS_TOKEN"];
				$this->FRIENDICA_URL = $_ENV["FRIENDICA_FR_URL"];
				$this->FRIENDICA_USERNAME = $_ENV["FRIENDICA_FR_USERNAME"];
				$this->FRIENDICA_PASSWORD = $_ENV["FRIENDICA_FR_PASSWORD"];
				break;
		}
	}

	public function getLanguages()
	{
		return ["fr"];
	}

	public function getLanguagesCanonical()
	{
		return [
			"Friendica (français)" => "friendica_fr"
		];
	}

	private function parseHTML($url) {
		$parser = new \App\Service\APParseHTML();
		$html = $parser->getContentURL($url);

		$dom = new \DOMDocument();
		@$dom->loadHTML($html);

		$res = [
			"title" => null,
			"description" => "",
			"image" => null
		];

		$meta_tags = $dom->getElementsByTagName('meta');

		foreach ($meta_tags as $meta) {
			$property = $meta->getAttribute('property');

			switch ($property) {
				case 'og:title':
					$res["title"] = $meta->getAttribute('content');
					break;
				case 'og:description':
					$res["description"] = is_null($content = $meta->getAttribute('content')) ? "" : $content;
					break;
				case 'og:image':
					$res["image"] = $meta->getAttribute('content');
					break;
			}
		}

		return $res;
	}
}