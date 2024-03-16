<?php

namespace App\Service;

class Bluesky {
	private $DID_URL = "https://bsky.social/xrpc/com.atproto.identity.resolveHandle";
	private $API_KEY_URL = "https://bsky.social/xrpc/com.atproto.server.createSession";
	private $FEED_URL = "https://bsky.social/xrpc/app.bsky.feed.getAuthorFeed";
	private $POST_FEED_URL = "https://bsky.social/xrpc/com.atproto.repo.createRecord";
	private $HANDLE = null;
	private $PASSWORD = null;

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
				'content' => json_encode(["identifier" => $identifier, "password" => $this->PASSWORD])
			]
		];

		$tokenRep = file_get_contents($this->API_KEY_URL, false, stream_context_create($tokenOpt));
		$tokenData = json_decode($tokenRep, true);
		
		return [$identifier, $tokenData['accessJwt']];
	}

	public function postMessage(string $message, string $url, string $locale) {
		$this->setLanguage($locale);

		list($identifier, $token) = $this->getToken();
		
		$facets = [];
		
		foreach($this->getTagsAndPositionsFromText($message) as $tag) {
			$facets[] = [
				"index" => ["byteStart" => $tag["start"], "byteEnd" => $tag["end"]],
				"features" => [["tag" => ltrim($tag["tag"], "#"), '$type' => "app.bsky.richtext.facet#tag"]],
			];
		}
		
		$data = $this->parseHTML($url);

		$postData = [
			"collection" => "app.bsky.feed.post",
			"repo" => $identifier,
			"record" => [
				"text" => $message,
				"createdAt" => date('c'),
				"type" => "app.bsky.feed.post",
				"facets" => $facets
			]
		];

		if(!empty($data["image"])) {
			$image = $this->uploadFile($data["image"], $locale);
			$postData = [
				"collection" => "app.bsky.feed.post",
				"repo" => $identifier,
				"record" => [
					'$type' => "app.bsky.feed.post",
					"text" => $message,
					"createdAt" => date('c'),
					'embed' => [
						'$type' => 'app.bsky.embed.external',
						'external' => [
							'uri' => $url,
							'title' => $data["title"],
							'description' => $data["description"],
							'thumb' => $image["blob"],
						],
					],
					"facets" => $facets
				],
			];
		}

		$postOpt = [
			CURLOPT_URL => $this->POST_FEED_URL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($postData),
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer " . $token,
				"Content-Type: application/json"
			]
		];

		$curl = curl_init();
		curl_setopt_array($curl, $postOpt);

		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}

		$response = curl_exec($curl);
		curl_close($curl);

		return json_decode($response);
	}

	public function uploadFile($file, $locale) {
		$this->setLanguage($locale);

		list($identifier, $token) = $this->getToken();

		$postData = [
			"data" => file_get_contents($file)
		];

		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$mimeType = $finfo->buffer(file_get_contents($file));
		finfo_close($finfo);

		$postOpt = [
			CURLOPT_URL => "https://bsky.social/xrpc/com.atproto.repo.uploadBlob",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => file_get_contents($file),
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer " . $token,
				"Content-Type: ".$mimeType
			]
		];

		$curl = curl_init();
		curl_setopt_array($curl, $postOpt);

		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}

		$response = curl_exec($curl);
		curl_close($curl);

		return json_decode($response, true);
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "en":
				$this->HANDLE = $_ENV["BLUESKY_EN_HANDLE"];
				$this->PASSWORD = $_ENV["BLUESKY_EN_PASSWORD"];
				break;
			case "es":
				$this->HANDLE = $_ENV["BLUESKY_ES_HANDLE"];
				$this->PASSWORD = $_ENV["BLUESKY_ES_PASSWORD"];
				break;
			case "fr":
				$this->HANDLE = $_ENV["BLUESKY_FR_HANDLE"];
				$this->PASSWORD = $_ENV["BLUESKY_FR_PASSWORD"];
				break;
		}
	}

	private function getTagsAndPositionsFromText($text) {
		$pattern = "/#(\w+)/";

		preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

		$res = [];

		foreach ($matches[0] as $match)
			$res[] = ["tag" => $match[0], "start" => $match[1], "end" => ($match[1] + strlen($match[0]))];

		return $res;
	}

	private function parseHTML($url) {
		$html = file_get_contents($url);

		$dom = new \DOMDocument();
		@$dom->loadHTML($html);

		$res = [
			"title" => null,
			"description" => null,
			"image" => null
		];

		$meta_tags = $dom->getElementsByTagName('meta');

		foreach ($meta_tags as $meta) {
			$property = $meta->getAttribute('property');

			switch ($property) {
				case 'og:title':
					$res["title"] = $meta->getAttribute('content');
					break;
				case 'og:description':
					$res["description"] = $meta->getAttribute('content');
					break;
				case 'og:image':
					$res["image"] = $meta->getAttribute('content');
					break;
			}
		}

		return $res;
	}

	public function getLanguages()
	{
		return ["en", "es", "fr"];
	}
}