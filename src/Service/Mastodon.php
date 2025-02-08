<?php

namespace App\Service;

class Mastodon {
	private $MASTODON_ACCESS_TOKEN = null;
	private $MASTODON_URL = null;

	public function postMessage(string $url, string $message, string $locale) {
		$this->setLanguage($locale);

		$accessToken = $this->MASTODON_ACCESS_TOKEN;
		$urlMastodon = $this->MASTODON_URL;
// dd($accessToken, $urlMastodon);
		$res = new \stdClass;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://${urlMastodon}/api/v1/statuses");
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
		} else {
			$data = json_decode($result);

			if(property_exists($data, "error")) {
				$res->error = new \stdClass();
				$res->error->message = $data->error;
			} else
				$res->success = "success";
		}

		curl_close($ch);

		return $res;
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "fr":
				$this->MASTODON_ACCESS_TOKEN = $_ENV["MASTODON_FR_ACCESS_TOKEN"];
				$this->MASTODON_URL = $_ENV["MASTODON_FR_URL"];
				break;
			case "en":
				$this->MASTODON_ACCESS_TOKEN = $_ENV["MASTODON_EN_ACCESS_TOKEN"];
				$this->MASTODON_URL = $_ENV["MASTODON_EN_URL"];
				break;
			case "es":
				$this->MASTODON_ACCESS_TOKEN = $_ENV["MASTODON_ES_ACCESS_TOKEN"];
				$this->MASTODON_URL = $_ENV["MASTODON_ES_URL"];
				break;
			case "pt":
				$this->MASTODON_ACCESS_TOKEN = $_ENV["MASTODON_PT_ACCESS_TOKEN"];
				$this->MASTODON_URL = $_ENV["MASTODON_PT_URL"];
				break;
			case "ru":
				$this->MASTODON_ACCESS_TOKEN = $_ENV["MASTODON_RU_ACCESS_TOKEN"];
				$this->MASTODON_URL = $_ENV["MASTODON_RU_URL"];
				break;
			case "magic_fr":
				$this->MASTODON_ACCESS_TOKEN = $_ENV["MASTODON_MAGIC_FR_ACCESS_TOKEN"];
				$this->MASTODON_URL = $_ENV["MASTODON_MAGIC_FR_URL"];
				break;
		}
	}

	public function getLanguages()
	{
		return ["fr", "en", "es", "pt", "ru", "magic_fr"];
	}

	public function getLanguagesCanonical()
	{
		return [
			"Mastodon (english)" => "mastodon_en",
			"Mastodon (español)" => "mastodon_es",
			"Mastodon (français)" => "mastodon_fr",
			"Mastodon (português)" => "mastodon_pt",
			"Mastodon (Русский)" => "mastodon_ru",
			"Mastodon (Magic FR)" => "mastodon_magic_fr"
		];
	}
}