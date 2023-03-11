<?php

	namespace App\Service;

	class Muse
	{
		private $url = null;
		
		public function __construct() {
			$this->url = $_ENV["MUSE_URL"];
		}
		
		public function getOauth2Token(): ?string
		{
			$username = $_ENV["MUSE_USERNAME"];
			$password = $_ENV["MUSE_PASSWORD"];

			$ch = curl_init();

			$data = ["username" => $username, "password" => $password];

			curl_setopt($ch, CURLOPT_URL, $this->url.'authentication_token');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

			$headers = array();
			$headers[] = 'Accept: application/json';
			$headers[] = 'Content-Type: application/json';
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				echo 'Error:' . curl_error($ch);
			}
			curl_close($ch);
// dd($result);
			return json_decode($result)->token;
		}
		
		public function addPost($data, $token)
		{
			$ch = curl_init();
			
			$url = $this->url."api/quotes";

			if(isset($data["identifier"]) and !empty($idt = $data["identifier"])) {
				$url .= "/".$idt;
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			} else
				curl_setopt($ch, CURLOPT_POST, true);

			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/ld+json", "Authorization: Bearer {$token}"));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$json_response = curl_exec($ch);
			$errors = curl_error($ch);
			curl_close($ch);
// dd($json_response);
			return json_decode($json_response);
		}
		
		public function addImage($entity, $image, $token)
		{
			$data = [
				"quote" => [
					"identifier" => $entity["identifier"],
				],
				"image" => $image["image"],
				"identifier" => $image["identifier"],
				"imgBase64" => $image["imgBase64"]
			];

			$ch = curl_init();
			
			$url = $this->url."api/quote_images";

			if(empty($image["imgBase64"])) {
				$url .= "/".$data["identifier"];
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			} else
				curl_setopt($ch, CURLOPT_POST, true);

			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/ld+json", "Authorization: Bearer {$token}"));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$json_response = curl_exec($ch);
			$errors = curl_error($ch);
			curl_close($ch);
dd($json_response);
			return json_decode($json_response);
		}

		public function getImages($identifier, $token) {
			$ch = curl_init();
			
			
			$url = $this->url."api/quotes/${identifier}/images";

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/ld+json", "Authorization: Bearer {$token}"));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$json_response = curl_exec($ch);
			$errors = curl_error($ch);
			curl_close($ch);
			
			$res = [];

			foreach(json_decode($json_response, true)["hydra:member"] as $data) {
				$res[] = [
				"imgBase64" => null,
				"identifier" => $data["identifier"],
				"image" =>  $data["image"]
				];
			}

			return $res;
		}
		
		public function getLocaleAvailable(): array {
			return ["fr", "en"];
		}
	}