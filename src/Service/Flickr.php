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
		$params = [
			'api_key' => $this->apiKey,
			'method' => $method,
			'photo_id' => $photoId,
			'format' => 'json',
			'nojsoncallback' => 1
		];

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
			"Attribution-NonCommercial-NoDerivs License" => "CC BY-NC-ND 2.0"
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
		$params = [
			'group_id' => $group_id,
			'method' => $method,
			'oauth_consumer_key' => $this->apiKey,
			'oauth_nonce' => $nonce,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => $timestamp,
			'oauth_token' => $this->oauthToken,
			'oauth_version' => '1.0',
			'photo_id' => $photo_id
		];

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
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);

		return $this->jsonResponse($response);
	}

	// https://www.flickr.com/services/api/upload.api.html
	public function uploadPhoto($title, $photo, $locale, $description = null, $tag = null) {
		$upload_url = 'https://up.flickr.com/services/upload/';

		$nonce = md5(microtime() . mt_rand());
		$timestamp = time();
		$sig_method = 'HMAC-SHA1';
		$oauth_version = "1.0";

		$params = [
			'oauth_nonce' => $nonce,
			'oauth_timestamp' => $timestamp,
			'oauth_consumer_key' => $this->apiKey,
			'oauth_token' => $this->oauthToken,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version' => '1.0'
		];

		if(!empty($title))
			$params["title"] = $title;
		if(!empty($description))
			$params["description"] = $description;
		if(!empty($tag))
			$params["tag"] = $tag;
		
        foreach ($params as $key => $value) {
            $signatureData[rawurlencode($key)] = rawurlencode($value);
        }

		ksort($signatureData);

        $signatureString = '';
        $delimiter = '';

        foreach ($signatureData as $key => $value) {
            $signatureString .= $delimiter . $key . '=' . $value;

            $delimiter = '&';
        }
	
		$base_string = 'POST&' . rawurlencode($upload_url) . '&' . rawurlencode($signatureString);

		$signature_key = $this->apiSecret . '&' . $this->oauthSecret;
		$params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));

		$file = new \CURLFile($this->convertWebPToJPG($photo));
		$fields = array_merge($params, ['photo' => $file]);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $upload_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		// Only in dev
		if($_ENV["APP_ENV"] == "dev") {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		
		$response = curl_exec($ch);

		curl_close($ch);

		$res = @simplexml_load_string($response);
		return !$res ? ["error" => $response] : ["success" => (string)$res->photoid];
	}

	public function setMeta($photoId, $title, $description = null, $tag = null) {
		$upload_url = 'https://api.flickr.com/services/rest';

		$nonce = md5(microtime() . mt_rand());
		$timestamp = time();
		$sig_method = 'HMAC-SHA1';
		$oauth_version = "1.0";

		$params = [
			'method' => "flickr.photos.setMeta",
			'oauth_nonce' => $nonce,
			'oauth_timestamp' => $timestamp,
			'oauth_consumer_key' => $this->apiKey,
			'oauth_token' => $this->oauthToken,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version' => '1.0',
			"photo_id" => $photoId
		];

		if(!empty($title))
			$params["title"] = $title;
		if(!empty($description))
			$params["description"] = $description;
		if(!empty($tag))
			$params["tag"] = $tag;

        foreach ($params as $key => $value) {
            $signatureData[rawurlencode($key)] = rawurlencode($value);
        }

		ksort($signatureData);

        $signatureString = '';
        $delimiter = '';

        foreach ($signatureData as $key => $value) {
            $signatureString .= $delimiter . $key . '=' . $value;

            $delimiter = '&';
        }
	
		$base_string = 'POST&' . rawurlencode($upload_url) . '&' . rawurlencode($signatureString);

		$signature_key = $this->apiSecret . '&' . $this->oauthSecret;
		$params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $upload_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$res = @simplexml_load_string($response);
		return !$res ? ["error" => $response] : ["success" => (string)$res->photoid];
	}

	public function deletePhoto($photoId) {
		$upload_url = 'https://api.flickr.com/services/rest';

		$nonce = md5(microtime() . mt_rand());
		$timestamp = time();
		$sig_method = 'HMAC-SHA1';
		$oauth_version = "1.0";

		$params = [
			'method' => "flickr.photos.delete",
			'oauth_nonce' => $nonce,
			'oauth_timestamp' => $timestamp,
			'oauth_consumer_key' => $this->apiKey,
			'oauth_token' => $this->oauthToken,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version' => '1.0',
			"photo_id" => $photoId
		];

        foreach ($params as $key => $value) {
            $signatureData[rawurlencode($key)] = rawurlencode($value);
        }

		ksort($signatureData);

        $signatureString = '';
        $delimiter = '';

        foreach ($signatureData as $key => $value) {
            $signatureString .= $delimiter . $key . '=' . $value;
            $delimiter = '&';
        }
	
		$base_string = 'POST&' . rawurlencode($upload_url) . '&' . rawurlencode($signatureString);

		$signature_key = $this->apiSecret . '&' . $this->oauthSecret;
		$params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $upload_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$res = @simplexml_load_string($response);
		return !$res ? ["error" => $response] : ["success" => (string)$res->photoid];
	}

	public function getCounts() {
		$userId = '188024915@N02'; // Remplacez par l'ID de l'utilisateur dont vous voulez obtenir le nombre total de photos

		$apiEndpoint = 'https://api.flickr.com/services/rest/';
		$params = [
			'method' => 'flickr.people.getInfo',
			'api_key' => $this->apiKey,
			'user_id' => $userId,
			'format' => 'json',
			'nojsoncallback' => 1,
		];

		$url = $apiEndpoint . '?' . http_build_query($params);

		$response = file_get_contents($url);
		$data = json_decode($response, true);
		
		return $data["person"]["photos"]["count"]["_content"];
	}
	
	public function getOldestPhoto() {
		$userId = '188024915@N02'; // Remplacez par l'ID de l'utilisateur dont vous voulez obtenir les photos

		$apiEndpoint = 'https://api.flickr.com/services/rest/';
		$params = [
			'method' => 'flickr.photos.search',
			'api_key' => $this->apiKey,
			'user_id' => $userId,
			'sort' => 'date-taken-asc', // Trie par date de prise de vue en ordre croissant
			'per_page' => 1,
			'page' => 1,
			'format' => 'json',
			'nojsoncallback' => 1,
		];

		$url = $apiEndpoint . '?' . http_build_query($params);

		$response = file_get_contents($url);
		$data = json_decode($response, true);

		if ($data['stat'] === 'ok' && isset($data['photos']['photo'][0])) {
			$oldestPhoto = $data['photos']['photo'][0];
			return $oldestPhoto['id'];
		} else {
			echo "No photos found.";
		}
		die;
	}

	// https://www.flickr.com/services/api/auth.oauth.html
	public function authentication() {
		$request_token_url = 'https://www.flickr.com/services/oauth/request_token';
		$callback_url = 'http://127.0.0.1:8080/test/flickrpost.php';

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

			$basestring = "oauth_consumer_key=".$this->apiKey."&oauth_nonce=".$nonce."&oauth_signature_method=".$sig_method."&oauth_timestamp=".$timestamp."&oauth_token=".$_GET['oauth_token']."&oauth_verifier=".$_GET['oauth_verifier']."&oauth_version=".$oauth_version;
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

		$basestring = "oauth_callback=".urlencode($callback_url)."&oauth_consumer_key=".$this->apiKey."&oauth_nonce=".$nonce."&oauth_signature_method=".$sig_method."&oauth_timestamp=".$timestamp."&oauth_version=".$oauth_version;
		$base_string = 'GET&' . urlencode($request_token_url) . '&' . urlencode($basestring);
		$signature_key = $this->apiSecret . '&';

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

	public function getParametersByLocale($locale) {
		switch($locale) {
			case "fr":
				$this->FLICK_GROUP_ID = '14860407@N20';
				break;
			case "en":
				$this->FLICK_GROUP_ID = '14864823@N20';
				break;
			case "es":
				$this->FLICK_GROUP_ID = '14896537@N20';
				break;
		}
		
		return $this->FLICK_GROUP_ID;
	}

	private function convertWebPToJPG($webpImagePath) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$type = finfo_file($finfo, $webpImagePath);
		finfo_close($finfo);

		if ($type == "image/webp") {
			$webpImage = imagecreatefromwebp($webpImagePath);
			$pathInfo = pathinfo($webpImagePath);

			if ($webpImage === false) {
				return $webpImagePath;
			}
		} elseif($type == "image/jpeg") {
			$webpImage = imagecreatefromjpeg($webpImagePath);
			$pathInfo = pathinfo($webpImagePath);
		} else {
			$webpImage = imagecreatefrompng($webpImagePath);
			$pathInfo = pathinfo($webpImagePath);
		}

		$outputImagePath = $pathInfo["dirname"].DIRECTORY_SEPARATOR.$pathInfo["filename"].".jpg";

		$result = imagejpeg($webpImage, $outputImagePath, 100); // 100 is the quality
		if ($result === false) {
			return $webpImagePath;
		}

		imagedestroy($webpImage);
		
		return $outputImagePath;
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