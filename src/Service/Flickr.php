<?php

namespace App\Service;

class Flickr {
	private $FLICK_GROUP_ID = null;
	private $apiKey = null;
	private $apiSecret = null;
	private $oauthToken = null;
	private $oauthSecret = null;

	public function __construct() {
		$this->apiKey = $_ENV["FLICKR_API_KEY"];
		$this->apiSecret = $_ENV["FLICKR_API_SECRET"];
		$this->oauthToken = $_ENV["FLICKR_OAUTH_TOKEN"];
		$this->oauthSecret = $_ENV["FLICKR_OAUTH_SECRET"];
	}

	public function getImageInfos($photoIdOrUrl) {
		$photoId = $photoIdOrUrl;

		if(filter_var($photoIdOrUrl, FILTER_VALIDATE_URL)) {
			$photoId = $this->getPhotoIdByUrl($photoIdOrUrl);
		}
		
		$baseUrl = 'https://api.flickr.com/services/rest/?';
		$method = 'flickr.photos.getInfo';
		$params = array(
			'api_key' => $this->apiKey,
			'method' => $method,
			'photo_id' => $photoId,
			'format' => 'json',
			'nojsoncallback' => 1
		);

		$requestUrl = $baseUrl . http_build_query($params);

		$ch = curl_init($requestUrl);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			echo 'cURL error: ' . curl_error($ch);
			exit;
		}

		curl_close($ch);

		$data = json_decode($response, true);

		if($data["stat"] == "fail")
			return null;
		
		$path = "https://live.staticflickr.com/{$data['photo']['server']}/{$data['photo']['id']}_{$data['photo']['secret']}.{$data['photo']['originalformat']}";

		$res = [
			"url" => "https://live.staticflickr.com/{$data['photo']['server']}/{$data['photo']['id']}_{$data['photo']['secret']}.{$data['photo']['originalformat']}",
			"title" => $data["photo"]["title"]["_content"],
			"description" => $data["photo"]["description"]["_content"],
			"user" => $data["photo"]["owner"]["realname"],
			"license" => $this->getLicense($data["photo"]["license"])
		];

		return $res;
	}
	
	public function getLicense($licenseId) {
		$baseUrl = 'https://api.flickr.com/services/rest/?';
		$method = 'flickr.photos.licenses.getInfo';
		$params = array(
			'api_key' => $this->apiKey,
			'method' => $method,
			'format' => 'json',
			'nojsoncallback' => 1
		);

		$requestUrl = $baseUrl . http_build_query($params);

		$ch = curl_init($requestUrl);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			echo 'cURL error: ' . curl_error($ch);
			exit;
		}

		curl_close($ch);

		$datas = json_decode($response, true);

		if($datas["stat"] == "fail")
			return null;

		return $this->getLicenseName($datas["licenses"]["license"][$licenseId]["name"]);
	}

	public function getLicenseName($titleLicense) {
		return [
			"All Rights Reserved" => "Copyright",
			"Attribution License" => "CC BY 2.0",
			"Attribution-NoDerivs License" => "CC BY-ND 2.0",
			"Attribution-NonCommercial-NoDerivs" => "CC BY-NC-ND 2.0",
			"Attribution-NonCommercial License" => "CC BY-NC 2.0",
			"Attribution-NonCommercial-ShareAlike License" => "CC BY-NC-SA 2.0",
			"Attribution-ShareAlike License" => "CC BY-SA 2.0",
			"No known copyright restrictions" => "Copyleft",
			"United States Government Work" => "Copyright",
			"Public Domain Dedication (CC0)" => "CC0",
			"Public Domain Mark" => "PDM 1.0",
		][$titleLicense];
	}

	// https://www.flickr.com/services/api/flickr.groups.pools.add.html
	public function postImageGroup($locale, $photo_id) {
		$this->getParametersByLocale($locale);

		$group_id = $this->FLICK_GROUP_ID;

		$rest_url = 'https://api.flickr.com/services/rest';
		$nonce = md5(microtime() . mt_rand());
		$timestamp = time();
		$sig_method = 'HMAC-SHA1';
		$oauth_version = "1.0";

		$method = 'flickr.groups.pools.add';
		$params = array(
			'group_id' => $group_id,
			'method' => $method,
			'oauth_consumer_key' => $this->apiKey,
			'oauth_nonce' => $nonce,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => $timestamp,
			'oauth_token' => $this->oauthToken,
			'oauth_version' => '1.0',
			'photo_id' => $photo_id
		);

		ksort($params);

		$base_string = 'POST&' . urlencode($rest_url) . '&' . urlencode(http_build_query($params));

		$signature_key = $this->apiSecret . '&' . $this->oauthSecret;
		$params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));

		$fields = $params;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $rest_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		return $this->jsonResponse($response);
	}

	// https://www.flickr.com/services/api/upload.api.html
	public function uploadPhoto($title, $locale, $description = null, $tag = null) {
		$upload_url = 'https://up.flickr.com/services/upload/';

		$nonce = md5(microtime() . mt_rand());
		$timestamp = time();
		$sig_method = 'HMAC-SHA1';
		$oauth_version = "1.0";

		$params = array(
			'oauth_nonce' => $nonce,
			'oauth_timestamp' => $timestamp,
			'oauth_consumer_key' => $this->apiKey,
			'oauth_token' => $this->oauthToken,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version' => '1.0',
			'title' => $title
		);

		if(!empty($description))
			$params["description"] = $description;
		if(!empty($tag))
			$params["tag"] = $tag;

		ksort($params);

		$base_string = 'POST&' . urlencode($upload_url) . '&' . urlencode(http_build_query($params));

		$signature_key = $this->apiSecret . '&' . $this->oauthSecret;
		$params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));

		$file = new CURLFile('C:\wamp64\www\test\00004-r-pro-iy8b36a.jpg');
		$fields = array_merge($params, array('photo' => $file));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $upload_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		return $this->jsonResponse($response);
	}

	// https://www.flickr.com/services/api/auth.oauth.html
	public function authentication() {
		$request_token_url = 'https://www.flickr.com/services/oauth/request_token';
		$callback_url = 'http://127.0.0.1:8080/test/flickrpost.php';
		$api_key = "38dd98d79a954aacd3309e9d234a925c";
		$api_secret = "f35b65c138761ec4";

		if(isset($_GET["oauth_verifier"])) {
			$nonce = md5(microtime() . mt_rand());
			$timestamp = time();
			$sig_method = 'HMAC-SHA1';
			$oauth_version = "1.0";

			$access_token_url = 'https://www.flickr.com/services/oauth/access_token';

			$fields = array(
				'oauth_nonce' => $nonce,
				'oauth_timestamp' => $timestamp,
				'oauth_consumer_key' => $this->apiKey,
				'oauth_token' => $_GET['oauth_token'],
				'oauth_verifier' => $_GET['oauth_verifier'],
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_version' => '1.0'
			);

			$basestring = "oauth_consumer_key=".$api_key."&oauth_nonce=".$nonce."&oauth_signature_method=".$sig_method."&oauth_timestamp=".$timestamp."&oauth_token=".$_GET['oauth_token']."&oauth_verifier=".$_GET['oauth_verifier']."&oauth_version=".$oauth_version;
			$base_string = 'GET&' . urlencode($access_token_url) . '&' . urlencode($basestring);

			$signature_key = $this->apiSecret . '&'.$_SESSION['oauth_token_secret'];

			$oauth_signature = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));
				
			$fields['oauth_signature'] = $oauth_signature;

			$fields_string = "";
			foreach($fields as $key=>$value) $fields_string .= "$key=".urlencode($value)."&";

			$fields_string = rtrim($fields_string,'&');

			$url = $access_token_url."?".$fields_string;

			$ch = curl_init();
			$timeout = 5;

			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$file_contents = curl_exec($ch);
			curl_close($ch);
			
			return $file_contents;
		}

		$nonce = md5(microtime() . mt_rand());
		$timestamp = time();
		$sig_method = 'HMAC-SHA1';
		$oauth_version = "1.0";

		$basestring = "oauth_callback=".urlencode($callback_url)."&oauth_consumer_key=".$api_key."&oauth_nonce=".$nonce."&oauth_signature_method=".$sig_method."&oauth_timestamp=".$timestamp."&oauth_version=".$oauth_version;
		$base_string = 'GET&' . urlencode($request_token_url) . '&' . urlencode($basestring);
		$signature_key = $api_secret . '&';

		$oauth_signature = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));

		$fields = array(
			'oauth_nonce' => $nonce,
			'oauth_timestamp' => $timestamp,
			'oauth_consumer_key' => $this->apiKey,
			'oauth_signature_method' => $sig_method,
			'oauth_version' => "1.0",
			'oauth_signature' => $oauth_signature,
			'oauth_callback' => $callback_url
		);

		$fields_string = "";
		foreach($fields as $key=>$value) $fields_string .= "$key=".urlencode($value)."&";

		$fields_string = rtrim($fields_string,'&');

		$url = $request_token_url."?".$fields_string;

		$ch = curl_init();
		$timeout = 5;

		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$file_contents = curl_exec($ch);
		curl_close($ch);

		parse_str($file_contents, $request_token);

		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

		$authorize_url = 'https://www.flickr.com/services/oauth/authorize';
		header('Location: ' . $authorize_url . '?oauth_token=' . $request_token['oauth_token']);

		return $request_token;
	}

	private function getParametersByLocale($locale) {
		switch($locale) {
			case "fr":
				$this->FLICK_GROUP_ID = '14860407@N20';
				break;
		}
	}

	private function jsonResponse($string) {
		$start_pos = strpos($string, '(');
		$end_pos = strrpos($string, ')');
		$json_string = substr($string, $start_pos + 1, $end_pos - $start_pos - 1);

		return json_decode($json_string, true);
	}

	private function getPhotoIdByUrl(string $url) {
		list($type, $userId, $photoId) = array_values(array_filter(explode("/", parse_url($url, PHP_URL_PATH))));
		
		return $photoId;
	}
	
	public function getLanguages() {
		return ["fr", "en", "es"];
	}
}