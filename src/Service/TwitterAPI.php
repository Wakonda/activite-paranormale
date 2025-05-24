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

		return $connection->post('tweets', $parameters);
	}

	public function postLink(string $message, string $locale)
	{
		$this->setLanguage($locale);

		$connection = new TwitterOAuth($this->CONSUMER_KEY, $this->CONSUMER_SECRET, $this->OAUTH_TOKEN, $this->OAUTH_TOKEN_SECRET);

		$connection->setApiVersion('2');

		$parameters['text'] = $message;

		return $connection->post('tweets', $parameters);
	}

	public function retweet(string $tweet_id, string $locale)
	{
		$this->setLanguage($locale);

		$connection = new TwitterOAuth($this->CONSUMER_KEY, $this->CONSUMER_SECRET, $this->OAUTH_TOKEN, $this->OAUTH_TOKEN_SECRET);

		$connection->setApiVersion('2');
		
		$userId = explode("-", $this->OAUTH_TOKEN)[0];

		$parameters['tweet_id'] = $tweet_id;

		return $connection->post('users/'.$userId.'/retweets', $parameters);
	}

	public function getUsernameById(string $id, string $locale)
	{
		$this->setLanguage($locale);

		$connection = new TwitterOAuth($this->CONSUMER_KEY, $this->CONSUMER_SECRET, $this->OAUTH_TOKEN, $this->OAUTH_TOKEN_SECRET);

		$connection->setApiVersion('1.1');

		$parameters['ids'] = $id;

		dd( $connection->get('users', $parameters, true));
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
			case "pt":
				$this->CONSUMER_KEY = $_ENV["TWITTER_PT_CONSUMER_KEY"];
				$this->CONSUMER_SECRET = $_ENV["TWITTER_PT_CONSUMER_SECRET"];
				$this->OAUTH_TOKEN_SECRET = $_ENV["TWITTER_PT_OAUTH_TOKEN_SECRET"];
				$this->OAUTH_TOKEN = $_ENV["TWITTER_PT_OAUTH_TOKEN"];
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
		return ["en", "es", "fr", "pt", "magic_fr"];
	}

	public function getLanguagesCanonical()
	{
		return [
			"Twitter (english)" => "twitter_en",
			"Twitter (español)" => "twitter_es",
			"Twitter (français)" => "twitter_fr",
			"Twitter (português)" => "twitter_pt",
			"Twitter (français - magie)" => "twitter_magic_fr"
		];
	}
}