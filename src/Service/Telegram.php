<?php

namespace App\Service;

class Telegram {
	private $TELEGRAM_BOT_API_TOKEN = null;
	private $TELEGRAM_CHANNEL_ID = null;

	public function postMessage(string $text, string $locale) {
		$this->setLanguage($locale);

		$botApiToken = $_ENV["TELEGRAM_BOT_API_TOKEN"];
		$channelId = $this->TELEGRAM_CHANNEL_ID;

		$query = http_build_query([
			'chat_id' => $channelId,
			'text' => $text,
		]);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botApiToken}/sendMessage?{$query}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

		// Only in dev
		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$result = curl_exec($ch);

		curl_close($ch);
		
		if($result->ok == 1)
			return ["success" => true];

		return ["error" => $result->error_code, "error_description" => $result->description];
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "fr":
				$this->TELEGRAM_CHANNEL_ID = $_ENV["TELEGRAM_FR_CHANNEL_ID"];
				break;
			case "en":
				$this->TELEGRAM_CHANNEL_ID = $_ENV["TELEGRAM_EN_CHANNEL_ID"];
				break;
			case "es":
				$this->TELEGRAM_CHANNEL_ID = $_ENV["TELEGRAM_ES_CHANNEL_ID"];
				break;
			case "ru":
				$this->TELEGRAM_CHANNEL_ID = $_ENV["TELEGRAM_RU_CHANNEL_ID"];
				break;
			case "pt":
				$this->TELEGRAM_CHANNEL_ID = $_ENV["TELEGRAM_PT_CHANNEL_ID"];
				break;
		}
	}

	public function getLanguages()
	{
		return ["fr", "en", "es", "pt", "ru"];
	}
}