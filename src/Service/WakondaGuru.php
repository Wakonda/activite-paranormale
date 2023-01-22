<?php

	namespace App\Service;

	class WakondaGuru
	{
		private $url = null;
		
		public function __construct() {
			$this->url = $_ENV["WAKONDAGURU_URL"];
		}
		
		public function getOauth2Token(): ?string
		{
			$username = $_ENV["WAKONDAGURU_USERNAME"];
			$password = $_ENV["WAKONDAGURU_PASSWORD"];

			$headers = [
				'Content-Type: application/json',
			];
			
			$data = [
				"email" => $username,
				"password" => $password
			];

			$curl = curl_init($this->url."auth/login");
		 
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		 
			$json_response = json_decode(curl_exec($curl));
			$errors = curl_error($curl);
			curl_close($curl);

			return !empty($json_response) ? $json_response->token : null;
		}
		
		public function getTags(?string $token): ?array
		{
			$headers = [
				'Content-Type: application/json',
				'Authorization: Basic '. $token
			];

			$curl = curl_init($this->url."api/get_tags");

			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		 
			$res = curl_exec($curl);
			$errors = curl_error($curl);
			curl_close($curl);

			if(!$res)
				return [];

			return json_decode($res)->datas;
		}
		
		public function addPost($data, $token)
		{
			$headers = [
				'Content-Type: application/json',
				'Authorization: Basic '. $token
			];

			$curl = curl_init($this->url."api/save_article");

			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		 
			$res = curl_exec($curl);
			$errors = curl_error($curl);
			curl_close($curl);
			
			die($res);
dd($errors, $res);
			return json_decode($res);
		}
		
		public function getLocaleAvailable(): array {
			return ["fr"];
		}
	}