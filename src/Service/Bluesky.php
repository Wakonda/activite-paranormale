<?php

namespace App\Service;

class Bluesky {
	private $DID_URL = "https://bsky.social/xrpc/com.atproto.identity.resolveHandle";
	private $API_KEY_URL = "https://bsky.social/xrpc/com.atproto.server.createSession";
	private $FEED_URL = "https://bsky.social/xrpc/app.bsky.feed.getAuthorFeed";
	private $POST_FEED_URL = "https://bsky.social/xrpc/com.atproto.repo.createRecord";
	private $HANDLE = null;
	private $APP_PASSWORD = null;

	private function getIdentifier() {
		$handleOpt = [
			'http' => [
				'method' => 'GET'
			]
		];

		$handleUrl = $this->DID_URL . "?handle=" . $this->HANDLE;
		$handleRep = file_get_contents($handleUrl, false, stream_context_create($handleOpt));
		$handleData = json_decode($handleRep, true);
		
		return $handleData['did'];
	}

	private function getToken() {
		$identifier = $this->getIdentifier();

		$tokenOpt = [
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: application/json\r\n",
				'content' => json_encode(["identifier" => $identifier, "password" => $this>APP_PASSWORD])
			]
		];

		$tokenRep = file_get_contents($this->API_KEY_URL, false, stream_context_create($tokenOpt));
		$tokenData = json_decode($tokenRep, true);
		
		return [$identifier, $tokenData['accessJwt']];
	}

	public function postMessage(string $message, string $locale) {
		$this->setLanguage($locale);

		list($identifier, $token) = $this->getToken();

		$postData = [
			"collection" => "app.bsky.feed.post",
			"repo" => $identifier,
			"record" => [
				"text" => $message,
				"createdAt" => date('c'),
				"type" => "app.bsky.feed.post"
			]
		 ];

		$postOpt = [
			'http' => [
				'method' => 'POST',
				'header' => "Authorization: Bearer " . $token . "\r\n" .
							"Content-Type: application/json\r\n",
				'content' => json_encode($postData)
			]
		];

		return json_decode(file_get_contents(POST_FEED_URL, false, stream_context_create($postOpt)));
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "en":
				$this->HANDLE = $_ENV["BLUESKY_EN_CONSUMER_KEY"];
				$this->APP_PASSWORD = $_ENV["BLUESKY_EN_CONSUMER_SECRET"];
				break;
			case "es":
				$this->HANDLE = $_ENV["BLUESKY_ES_CONSUMER_KEY"];
				$this->APP_PASSWORD = $_ENV["BLUESKY_ES_CONSUMER_SECRET"];
				break;
			case "fr":
				$this->HANDLE = $_ENV["BLUESKY_FR_CONSUMER_KEY"];
				$this->APP_PASSWORD = $_ENV["BLUESKY_FR_CONSUMER_SECRET"];
				break;
		}
	}
}