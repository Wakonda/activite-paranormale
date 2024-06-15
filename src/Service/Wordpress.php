<?php

namespace App\Service;

class Wordpress {

	public function postMessage($data) {
		$username = $_ENV["WORDPRESS_USERNAME"];
		$password = $_ENV["WORDPRESS_PASSWORD"];
		$url = $_ENV["WORDPRESS_URL"];
		
		$url .= '/wp-json/myplugin/v1/data';

		// Example request
		/*$data = array(
		'_id' => "video_123",
			"image" => "http://example.com/test.webp",
			'title' => "title",
			'text' => "text",
			'date' => date('Y-m-d H:i:s'),
			"username" => "author",
			"tags" => ["Video"],
			"categories" => ["Alien"]
		);*/

		$ch = curl_init($url);

		$jsonData = json_encode($data);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($jsonData)
		]);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

		$response = curl_exec($ch);

		if ($response === false) {
			$responseData = curl_error($ch);
		} else {
			$responseData = json_decode($response, true);
		}

		curl_close($ch);

		return $responseData;
	}

	public function getLanguages()
	{
		return ["fr"];
	}
}