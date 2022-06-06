<?php

	namespace App\Service;

	class TheDailyTruth
	{
		private $url = null;
		
		public function __construct() {
			$this->url = $_ENV["THEDAILYTRUTH_URL"];
		}
		
		public function getOauth2Token(): ?string
		{
			$username = $_ENV["THEDAILYTRUTH_EMAIL"];
			$password = $_ENV["THEDAILYTRUTH_PASSWORD"];

			$headers = array(
				'Content-Type: application/json',
				'Authorization: Basic '. base64_encode("$username:$password")
			);

			$curl = curl_init($this->url."user/login_api");
		 
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		 
			$json_response = json_decode(curl_exec($curl));
			$errors = curl_error($curl);
			curl_close($curl);

			return !empty($json_response) ? $json_response->token : null;
		}
		
		public function getTags(?string $token): ?array
		{
			$curl = curl_init($this->url."admin/article/api/tags");

			curl_setopt($curl, CURLOPT_HTTPHEADER, ["x-access-tokens: ".$token]);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

			$json_response = curl_exec($curl);
			$errors = curl_error($curl);
			curl_close($curl);

			return json_decode($json_response);
		}
		
		public function addPost($data, $token)
		{
			$curl = curl_init($this->url."admin/article/api/new");

			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, ["x-access-tokens: ".$token]);
			curl_setopt($curl, CURLOPT_POSTFIELDS, ($data));
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		 
			$json_response = curl_exec($curl);
			$errors = curl_error($curl);
			curl_close($curl);

			return json_decode($json_response);
		}
		
		public function getLocaleAvailable(): array {
			return ["fr"];
		}
	}