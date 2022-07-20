<?php

namespace App\Service;

class Mastodon {
	private $MASTODON_ACCESS_TOKEN = null;

	public function postMessage(string $url, string $message, string $locale) {
		$this->setLanguage($locale);

		$accessToken = $this->MASTODON_ACCESS_TOKEN;

		$res = new \stdClass;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://mastodon.social/api/v1/statuses');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, ['status' => $message." ".$url]);

		// Only in dev
		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$headers = [];
		$headers[] = 'Authorization: Bearer '.$accessToken;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$res->error = curl_error($ch);
		} else
			$res->success = "success";

		curl_close($ch);

		return $res;
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "fr":
				$this->MASTODON_ACCESS_TOKEN = $_ENV["MASTODON_FR_ACCESS_TOKEN"];
				break;
		}
	}

	public function getLanguages()
	{
		return ["fr"];
	}
}