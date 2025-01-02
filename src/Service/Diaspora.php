<?php

namespace App\Service;

class Diaspora {
	// https://diaspora.github.io/api-documentation/authentication.html

	private $DIASPORA_URL = null;
	private $DIASPORA_CLIENT_ID = null;
	private $DIASPORA_CLIENT_SECRET = null;
	private $DIASPORA_CLIENT_NAME = null;
	private $DIASPORA_SCOPE = null;
	
	public $FILE_PATH = "../private/diaspora_openid.txt";
	
	public function getOpenIDConfiguration(string $locale)
	{
		$this->setLanguage($locale);
		$url = "{$this->DIASPORA_URL}.well-known/openid-configuration";

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'GET');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		return json_decode($result);
	}

	public function getClients(string $url, string $locale)
	{
		$this->setLanguage($locale);

		$postFields = [
			"client_name" => $this->DIASPORA_CLIENT_NAME,
			"redirect_uris" => [
				$url
			]
		];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "{$this->DIASPORA_URL}api/openid_connect/clients");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$headers = [];
		$headers[] = 'Content-Type: application/json';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		$result = json_decode($result);

		return [
			"client_id" => $result->client_id,
			"client_secret" => $result->client_secret
		];
	}

	public function getCode(string $redirect_uri, string $locale)
	{
		$this->setLanguage($locale);

		$scope = $this->DIASPORA_SCOPE;

		$redirect_uri = urlencode($redirect_uri);

		$loginUrl = "{$this->DIASPORA_URL}api/openid_connect/authorizations/new?response_type=code&client_id={$this->DIASPORA_CLIENT_ID}&redirect_uri=${redirect_uri}&scope=${scope}";

		if(!isset($_GET['code']))
		{
			header("Location: ".$loginUrl);
			die;
		}

		return $_GET['code'];
	}

	public function getAccessToken(string $url, string $code, string $locale)
	{
		$this->setLanguage($locale);

		$datas = [
			"client_id" => $this->DIASPORA_CLIENT_ID,
			"client_secret" => $this->DIASPORA_CLIENT_SECRET,
			"grant_type" => "authorization_code",
			"code" => $code,
			"redirect_uri" => $url,
		];
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "{$this->DIASPORA_URL}api/openid_connect/access_tokens");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datas));

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$headers = [];
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = json_decode(curl_exec($ch), true);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		$tokenInfos = json_decode(file_get_contents($this->FILE_PATH), true);
		$tokenInfos[$locale] = $result;

		file_put_contents($this->FILE_PATH, json_encode($tokenInfos));

		return $result["access_token"];
	}
	
	public function getUserInfo(string $accessToken)
	{
		$userinfoEndpoint = "https://diaspora-fr.org/api/openid_connect/user_info";

		$options = array(
			CURLOPT_URL => $userinfoEndpoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer $accessToken"
			)
		);

		$curl = curl_init();
		curl_setopt_array($curl, $options);

		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$response = json_decode(curl_exec($curl));
		
		return $response;
	}
	
	public function getAuthTokenByRefreshToken(string $refreshToken, string $locale)
	{
		$this->setLanguage($locale);

		$datas = [
			"client_id" => $this->DIASPORA_CLIENT_ID,
			"client_secret" => $this->DIASPORA_CLIENT_SECRET,
			"grant_type" => "refresh_token",
			"refresh_token" => $refreshToken
		];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "{$this->DIASPORA_URL}api/openid_connect/access_tokens");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datas));

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$headers = array();
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		$result = json_decode($result, true);
		$tokenInfos = json_decode(file_get_contents($this->FILE_PATH), true);
		$tokenInfos[$locale] = $result;

		file_put_contents($this->FILE_PATH, json_encode($tokenInfos));

		return $result["access_token"];
	}
	
	public function postMessage(string $message, string $access_token, string $locale)
	{
		$this->setLanguage($locale);

		$url = "{$this->DIASPORA_URL}api/v1/posts?access_token=$access_token";

		$params = [
			"body" => $message,
			"public" => true
		];

		$data = http_build_query($params);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$result = curl_error($ch);
		}
		curl_close($ch);

		return json_decode($result);
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "fr":
				$this->DIASPORA_URL = $_ENV["DIASPORA_FR_URL"];
				$this->DIASPORA_CLIENT_ID = $_ENV["DIASPORA_FR_CLIENT_ID"];
				$this->DIASPORA_CLIENT_SECRET = $_ENV["DIASPORA_FR_CLIENT_SECRET"];
				$this->DIASPORA_CLIENT_NAME = $_ENV["DIASPORA_FR_CLIENT_NAME"];
				$this->DIASPORA_SCOPE = $_ENV["DIASPORA_FR_SCOPE"];
				break;
			case "en":
				$this->DIASPORA_URL = $_ENV["DIASPORA_EN_URL"];
				$this->DIASPORA_CLIENT_ID = $_ENV["DIASPORA_EN_CLIENT_ID"];
				$this->DIASPORA_CLIENT_SECRET = $_ENV["DIASPORA_EN_CLIENT_SECRET"];
				$this->DIASPORA_CLIENT_NAME = $_ENV["DIASPORA_EN_CLIENT_NAME"];
				$this->DIASPORA_SCOPE = $_ENV["DIASPORA_EN_SCOPE"];
				break;
			case "es":
				$this->DIASPORA_URL = $_ENV["DIASPORA_ES_URL"];
				$this->DIASPORA_CLIENT_ID = $_ENV["DIASPORA_ES_CLIENT_ID"];
				$this->DIASPORA_CLIENT_SECRET = $_ENV["DIASPORA_ES_CLIENT_SECRET"];
				$this->DIASPORA_CLIENT_NAME = $_ENV["DIASPORA_ES_CLIENT_NAME"];
				$this->DIASPORA_SCOPE = $_ENV["DIASPORA_ES_SCOPE"];
				break;
			case "pt":
				$this->DIASPORA_URL = $_ENV["DIASPORA_PT_URL"];
				$this->DIASPORA_CLIENT_ID = $_ENV["DIASPORA_PT_CLIENT_ID"];
				$this->DIASPORA_CLIENT_SECRET = $_ENV["DIASPORA_PT_CLIENT_SECRET"];
				$this->DIASPORA_CLIENT_NAME = $_ENV["DIASPORA_PT_CLIENT_NAME"];
				$this->DIASPORA_SCOPE = $_ENV["DIASPORA_PT_SCOPE"];
				break;
		}
	}

	public function getLanguages()
	{
		return ["fr", "en", "es", "pt"];
	}

	public function getLanguagesCanonical()
	{
		return [
			"Diaspora (english)" => "mastodon_en",
			"Diaspora (español)" => "mastodon_es",
			"Diaspora (français)" => "mastodon_fr",
			"Diaspora (português)" => "mastodon_pt"
		];
	}
}