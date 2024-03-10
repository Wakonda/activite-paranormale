<?php

	namespace App\Service;

	class VK {
		// https://vk.com/dev/implicit_flow_user
		// https://vk.com/dev/authcode_flow_user
		// https://dev.vk.com/ru
		private $ownerId = null;
		private $clientId = null;
		private $clientSecret = null;
		private $version = null;
		private $accessToken = null;

		public function __construct() {
			$this->ownerId = $_ENV["VK_OWNER_ID"];
			$this->clientId = $_ENV["VK_CLIENT_ID"];
			$this->clientSecret = $_ENV["VK_CLIENT_SECRET"];
			$this->version = $_ENV["VK_VERSION"];
			$this->accessToken = $_ENV["VK_ACCESS_TOKEN"];
		}
		
		// https://stackoverflow.com/questions/41494966/vk-api-access-denied-for-post-on-wall-of-a-community-fail-wall-permissions
		// to get access_token:
		// Past this url in web browser: 
		// $redirectURI = urlencode("https://oauth.vk.com/blank.html");
		// $url = "https://oauth.vk.com/authorize?client_id={$this->clientId}&display=page&redirect_uri={$redirectURI}&scope=offline,photos,wall,groups&response_type=token&v={$this->version}";
		// Follow step and copy access_token in .env file
		public function getCode($redirectURI) {
			$redirectURI = urlencode("https://oauth.vk.com/blank.html");
			$url = "https://oauth.vk.com/authorize?client_id={$this->clientId}&display=page&redirect_uri={$redirectURI}&scope=offline,photos,wall,groups&response_type=code&v={$this->version}";

			if(!isset($_GET['code']))
			{
				header("Location: ".$url);
				die;
			}

			return $_GET['code'];
		}

		public function getAccessToken($redirectURI, $code) {
			$redirectURI = urlencode($redirectURI);
			$url = "https://oauth.vk.com/access_token?client_id={$this->clientId}&client_secret={$this->clientSecret}&redirect_uri={$redirectURI}&code={$code}";

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$response = curl_exec($ch);

			if(curl_errno($ch)){
				echo 'Curl error: ' . curl_error($ch);
			}

			curl_close($ch);

			return json_decode($response);
		}
		
		public function postMessage($content, $url)
		{
			$baseUrl = "https://api.vk.com/method/wall.post";

			$params = [
				'owner_id' => "-".$this->ownerId,
				'message' => $content,
				"attachments" => $url,
				'access_token' => $this->accessToken,
				'v' => $this->version
			];

			$ch = curl_init();

			$url = $baseUrl . '?' . http_build_query($params);

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$response = curl_exec($ch);

			if(curl_errno($ch)){
				echo 'Curl error: ' . curl_error($ch);
			}

			curl_close($ch);

			return json_decode($response);
		}

		public function getLanguages() {
			return ["fr", "en", "es", "ru"];
		}
	}