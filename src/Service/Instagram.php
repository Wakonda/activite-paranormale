<?php

namespace App\Service;

class Instagram {
	// Meta Business suite : https://business.facebook.com/
	// https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived

	private $FACEBOOK_PAGE_ID = null;
	private $FACEBOOK_APP_ID = null;
	private $FACEBOOK_SECRET_KEY = null;
	private $FACEBOOK_GRAPH_VERSION = null;
	private $FACEBOOK_USER_ID = null;
	private $FACEBOOK_ACCESS_TOKEN = null;
	private $FACEBOOK_USER_INSTAGRAM_ID = null;

	public function getLongLiveAccessToken() {
		$this->setLanguage($locale);

		$accessToken = ""; // Token généré à partir de la page : https://developers.facebook.com/tools/explorer/
		$pageId = $this->FACEBOOK_PAGE_ID;
		$appId = $this->FACEBOOK_APP_ID;
		$secretKey = $this->FACEBOOK_SECRET_KEY;
		$apiGraphVersion = $this->FACEBOOK_GRAPH_VERSION;
		$userId = $this->FACEBOOK_USER_ID; // GET me?fields=id,name
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/{$apiGraphVersion}/oauth/access_token?grant_type=fb_exchange_token&client_id={$appId}&client_secret={$secretKey}&fb_exchange_token={$accessToken}");
			
		// Only on dev
		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
			
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		$result = json_decode($result);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/{$apiGraphVersion}/{$userId}/accounts?access_token=".$result->access_token);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// Only on dev
		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		return json_decode($result);
	}

	public function getInstagramUser($locale) {
		$this->setLanguage($locale);

		$accessToken = $this->FACEBOOK_ACCESS_TOKEN;
		$pageId = $this->FACEBOOK_PAGE_ID;
		$appId = $this->FACEBOOK_APP_ID;
		$secretKey = $this->FACEBOOK_SECRET_KEY;
		$apiGraphVersion = $this->FACEBOOK_GRAPH_VERSION;
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/{$apiGraphVersion}/{$pageId}?fields=instagram_business_account&access_token={$accessToken}");
			
		// Only on dev
		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
			
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		$result = json_decode($result);

		return $result->id;
	}
	
	public function addMediaMessage($image_url, $caption, $locale) {
		$this->setLanguage($locale);

		$accessToken = $this->FACEBOOK_ACCESS_TOKEN;
		$pageId = $this->FACEBOOK_PAGE_ID;
		$appId = $this->FACEBOOK_APP_ID;
		$secretKey = $this->FACEBOOK_SECRET_KEY;
		$apiGraphVersion = $this->FACEBOOK_GRAPH_VERSION;
		$userId = $this->FACEBOOK_USER_INSTAGRAM_ID;
		
		$caption = urlencode($caption);
		$image_url = urlencode($image_url);

		$instagramURL = "https://graph.facebook.com/{$apiGraphVersion}/{$userId}/media?image_url={$image_url}&is_carousel_item=0&caption={$caption}&access_token={$accessToken}";

		$ch = curl_init();
		
		$llt = $this->FACEBOOK_ACCESS_TOKEN;

		curl_setopt($ch, CURLOPT_URL, $instagramURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);

		// Only on dev
		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);
		
		$result = json_decode($result);

		if(property_exists($result, "id")) {
			$id = $result->id;
			
			$ch = curl_init();

			$instagramURL = "https://graph.facebook.com/{$apiGraphVersion}/{$userId}/media_publish?creation_id={$id}&access_token={$accessToken}";

			curl_setopt($ch, CURLOPT_URL, $instagramURL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);

			// Only on dev
			if($_ENV["APP_ENV"] == "dev") {
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			}

			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				echo 'Error:' . curl_error($ch);
			}
			curl_close($ch);
		}

		return $result;
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "fr":
				$this->FACEBOOK_PAGE_ID = $_ENV["FACEBOOK_FR_PAGE_ID"];
				$this->FACEBOOK_APP_ID = $_ENV["FACEBOOK_FR_APP_ID"];
				$this->FACEBOOK_SECRET_KEY = $_ENV["FACEBOOK_FR_SECRET_KEY"];
				$this->FACEBOOK_GRAPH_VERSION = $_ENV["FACEBOOK_FR_GRAPH_VERSION"];
				$this->FACEBOOK_USER_ID = $_ENV["FACEBOOK_FR_USER_ID"];
				$this->FACEBOOK_ACCESS_TOKEN = $_ENV["FACEBOOK_FR_ACCESS_TOKEN"];
				$this->FACEBOOK_USER_INSTAGRAM_ID = $_ENV["FACEBOOK_FR_USER_INSTAGRAM_ID"];
				break;
		}
	}

	public function getLanguages()
	{
		return ["fr"];
	}
}