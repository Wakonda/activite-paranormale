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

		$parameters = array(
			'status' => $message
		);

		if(!empty($image)) {
			$media = $connection->upload('media/upload', array('media' => $image));
			$parameters['media_ids'] = implode(',', array($media->media_id_string));
		}

		return $connection->post('statuses/update', $parameters);
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
				$this->TWITTER_USERNAME = "WakondaEn";
				break;
			case "es":
				$this->CONSUMER_KEY = $_ENV["TWITTER_ES_CONSUMER_KEY"];
				$this->CONSUMER_SECRET = $_ENV["TWITTER_ES_CONSUMER_SECRET"];
				$this->OAUTH_TOKEN = $_ENV["TWITTER_ES_OAUTH_TOKEN"];
				$this->OAUTH_TOKEN_SECRET = $_ENV["TWITTER_ES_OAUTH_TOKEN_SECRET"];
				$this->TWITTER_USERNAME = "WakondaEs";
				break;
			case "fr":
				$this->CONSUMER_KEY = $_ENV["TWITTER_FR_CONSUMER_KEY"];
				$this->CONSUMER_SECRET = $_ENV["TWITTER_FR_CONSUMER_SECRET"];
				$this->OAUTH_TOKEN = $_ENV["TWITTER_FR_OAUTH_TOKEN"];
				$this->OAUTH_TOKEN_SECRET = $_ENV["TWITTER_FR_OAUTH_TOKEN_SECRET"];
				$this->TWITTER_USERNAME = "Wakonda1";
		}
	}

	public function getLanguages()
	{
		return ["en", "es", "fr"];
	}
}