<?php
namespace App\Service;

use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterAPI
{
	private $CONSUMER_KEY = null;
	private $CONSUMER_SECRET = null;
	private $OAUTH_TOKEN = null;
	private $OAUTH_TOKEN_SECRET = null;
	private $TWITTER_USERNAME = null;

	public function sendTweet(string $message, string $locale, $image = false)
	{
		$this->setLanguage($locale);

		$connection = new TwitterOAuth($this->CONSUMER_KEY, $this->CONSUMER_SECRET, $this->OAUTH_TOKEN, $this->OAUTH_TOKEN_SECRET);

		if(!empty($image)) {
			$imageArray = [];
			$connection->setApiVersion('1.1');
			$media = $connection->upload('media/upload', ['media' => $image]);
			array_push($imageArray, $media->media_id_string);

			$parameters['media']['media_ids'] = $imageArray;
		}
		
		$connection->setApiVersion('2');

		$parameters['text'] = $message;

		return $connection->post('tweets', $parameters, true);
	}

	public function retweet(string $message, string $locale)
	{
		$this->setLanguage($locale);

		$connection = new TwitterOAuth($this->CONSUMER_KEY, $this->CONSUMER_SECRET, $this->OAUTH_TOKEN, $this->OAUTH_TOKEN_SECRET);

		$connection->setApiVersion('2');

		$parameters['text'] = $message;

		return $connection->post('tweets', $parameters, true);
	}
	
	public function setLanguage($language)
	{
		switch($language)
		{
			case "en":
				$this->CONSUMER_KEY = $_ENV["TWITTER_EN_CONSUMER_KEY"];
				$this->CONSUMER_SECRET = $_ENV["TWITTER_EN_CONSUMER_SECRET"];
				$this->OAUTH_TOKEN = $_ENV["TWITTER_EN_OAUTH_TOKEN"];
				$this->OAUTH_TOKEN_SECRET = $_ENV["TWITTER_EN_OAUTH_TOKEN_SECRET"];
				break;
			case "es":
				$this->CONSUMER_KEY = $_ENV["TWITTER_ES_CONSUMER_KEY"];
				$this->CONSUMER_SECRET = $_ENV["TWITTER_ES_CONSUMER_SECRET"];
				$this->OAUTH_TOKEN = $_ENV["TWITTER_ES_OAUTH_TOKEN"];
				$this->OAUTH_TOKEN_SECRET = $_ENV["TWITTER_ES_OAUTH_TOKEN_SECRET"];
				break;
			case "fr":
				$this->CONSUMER_KEY = $_ENV["TWITTER_FR_CONSUMER_KEY"];
				$this->CONSUMER_SECRET = $_ENV["TWITTER_FR_CONSUMER_SECRET"];
				$this->OAUTH_TOKEN = $_ENV["TWITTER_FR_OAUTH_TOKEN"];
				$this->OAUTH_TOKEN_SECRET = $_ENV["TWITTER_FR_OAUTH_TOKEN_SECRET"];
				break;
			case "magic_fr":
				$this->CONSUMER_KEY = $_ENV["TWITTER_MAGIC_FR_CONSUMER_KEY"];
				$this->CONSUMER_SECRET = $_ENV["TWITTER_MAGIC_FR_CONSUMER_SECRET"];
				$this->OAUTH_TOKEN = $_ENV["TWITTER_MAGIC_FR_OAUTH_TOKEN"];
				$this->OAUTH_TOKEN_SECRET = $_ENV["TWITTER_MAGIC_FR_OAUTH_TOKEN_SECRET"];
		}
	}

	public function getLanguages()
	{
		return ["en", "es", "fr", "magic_fr"];
	}

	public function getLanguagesCanonical()
	{
		return [
			"Twitter (english)" => "en",
			"Twitter (español)" => "es",
			"Twitter (français)" => "fr",
			"Twitter (français - magie)" => "magic_fr"
		];
	}
}