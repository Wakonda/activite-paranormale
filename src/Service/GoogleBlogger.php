<?php

	namespace App\Service;

	class GoogleBlogger
	{
		private $email = "amatukami@hotmail.fr";
		private $state = "profile"; //optional - could be whatever value you want
		private $access_type = "offline"; //optional - allows for retrieval of refresh_token for offline access
		private $scope = "https://www.googleapis.com/auth/blogger";
		private $oauth2token_url = "https://accounts.google.com/o/oauth2/token";
		
		private $blogId_array = array(
			"JakinEtBoaz" => "4192778394306065291",
			"BookOfLucifer" => "8587307742034849671",
			"PrieresEtSortileges" => "1611333979864196065",
			"Amatukami" => "3616068588689105914",
			"Wakonda666" => "976120769055861867",
			"ActiviteParanormale" => "6843544030232757764",
			"TheTempleOfZebuleon" => "3619394577589453859",
			"ElGrimorioDeAstaroth" => "6143285371855196758",
			"Test" => "2865018866226462436"
		);
		
		public function getBlogURLArray(string $blogName) {
			$blogName = $_ENV["APP_ENV"] == "dev" ? "Test" : $blogName;
			
			return [
				"JakinEtBoaz" => "https://jakin-boaz.blogspot.fr/",
				"BookOfLucifer" => "https://bookoflucifer.blogspot.fr",
				"PrieresEtSortileges" => "https://prieres-et-sortileges.blogspot.fr/",
				"Amatukami" => "https://amatukami.blogspot.fr/",
				"Wakonda666" => "https://wakonda666.blogspot.fr/",
				"ActiviteParanormale" => "https://activite-paranormale.blogspot.fr/",
				"TheTempleOfZebuleon" => "https://thetempleofzebuleon.blogspot.fr",
				"ElGrimorioDeAstaroth" => "https://elgrimoriodeastaroth.blogspot.fr",
				"Test" => "https://testap7.blogspot.fr"
			][$blogName];
		}
		
		public function getCode($redirect_uri)
		{
			$loginUrl = sprintf("https://accounts.google.com/o/oauth2/auth?scope=%s&state=%s&redirect_uri=%s&response_type=code&client_id=%s&access_type=%s&login_hint=%s", $this->scope, $this->state, $redirect_uri, $_ENV["BLOGGER_CLIENT_ID"], $this->access_type, $this->email);
			
			if(!isset($_GET['code']))
			{
				header("Location: ".$loginUrl);
				die;
			}

			return $_GET['code'];
		}
		
		public function getOauth2Token($grantCode, $grantType, $redirect_uri)
		{
			$clienttoken_post = array(
				"client_id" => $_ENV["BLOGGER_CLIENT_ID"],
				"client_secret" => $_ENV["BLOGGER_CLIENT_SECRET"],
				"scope" => ""
			);
		 
			if ($grantType === "online"){
				$clienttoken_post["code"] = $grantCode;
				$clienttoken_post["redirect_uri"] = $redirect_uri;
				$clienttoken_post["grant_type"] = "authorization_code";
			}
			 
			if ($grantType === "offline"){
				$clienttoken_post["refresh_token"] = $grantCode;
				$clienttoken_post["grant_type"] = "refresh_token";
			}
			 
			$curl = curl_init($this->oauth2token_url);
		 
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $clienttoken_post);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		 
			$json_response = curl_exec($curl);
			curl_close($curl);
		 
			$authObj = json_decode($json_response);
			 
			//if offline access requested and granted, get refresh token
			if (isset($authObj->refresh_token)){
				global $refreshToken;
				$refreshToken = $authObj->refresh_token;
			}

			$accessToken = $authObj->access_token;

			return $accessToken;
		}

		public function getPostInfos($blogTitle)
		{
			$blogTitle = $_ENV["APP_ENV"] == "dev" ? "Test" : $blogTitle;
			$blogId = $this->blogId_array[$blogTitle];
			$curlObj = curl_init();
			
			// Get blog information
			curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/blogger/v3/blogs/'.$blogId.'?key='.$_ENV["BLOGGER_API_KEY"]);
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlObj, CURLOPT_HEADER, 0);
			curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
			 
			$response = curl_exec($curlObj);
			
			return $response;
		}
		
		public function addPost($blogTitle, $accessToken, $title, $content, $tags = array())
		{
			$blogTitle = $_ENV["APP_ENV"] == "dev" ? "Test" : $blogTitle;
			$blogId = $this->blogId_array[$blogTitle];
			$data = array("kind" => "blogger#post", "blog" => array("id" => $blogId), "title" => $title, "content" => $content, "labels" => json_decode($tags));  
			$data = json_encode($data);

			$curlObj = curl_init();

			curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/blogger/v3/blogs/'.$blogId.'/posts?key='.$_ENV["BLOGGER_API_KEY"].'&alt=json');
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlObj, CURLOPT_POST, 1);
			curl_setopt($curlObj, CURLOPT_HEADER, false);
			curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json', 'Authorization: OAuth '.$accessToken));
			curl_setopt($curlObj, CURLOPT_POSTFIELDS, $data);

			$response = curl_exec($curlObj);
			
			$httpCode = curl_getinfo($curlObj, CURLINFO_HTTP_CODE); 
			
			curl_close($curlObj);

			return array("http_code" => $httpCode, "response" => $response);
		}
		
		public function updatePost($idPostBlogger, $blogTitle, $accessToken, $title, $content, $tags = [])
		{
			$blogTitle = $_ENV["APP_ENV"] == "dev" ? "Test" : $blogTitle;
			$blogId = $this->blogId_array[$blogTitle];
			$data = array("kind" => "blogger#post", "id" => $idPostBlogger, "blog" => array("id" => $blogId), "title" => $title, "content" => $content, "labels" => json_decode($tags));

			$curlObj = curl_init();

			curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/blogger/v3/blogs/'.$blogId.'/posts/'.$idPostBlogger.'?key='.$_ENV["BLOGGER_API_KEY"].'&alt=json');
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlObj, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($curlObj, CURLOPT_POST, 1);
			curl_setopt($curlObj, CURLOPT_HEADER, false);
			curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: OAuth '.$accessToken));
			curl_setopt($curlObj, CURLOPT_POSTFIELDS, json_encode($data));

			$response = curl_exec($curlObj);
			$error = curl_error($curlObj);
			$httpCode = curl_getinfo($curlObj, CURLINFO_HTTP_CODE); 
			
			curl_close($curlObj);

			return array("http_code" => $httpCode, "response" => $response);
		}
		
		public function deletePost($idPostBlogger, $blogTitle, $accessToken) {
			$blogTitle = $_ENV["APP_ENV"] == "dev" ? "Test" : $blogTitle;
			$blogId = $this->blogId_array[$blogTitle];
			
			$curlObj = curl_init();

			curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/blogger/v3/blogs/'.$blogId.'/posts/'.$idPostBlogger.'?key='.$_ENV["BLOGGER_API_KEY"].'&alt=json');
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlObj, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($curlObj, CURLOPT_HEADER, false);
			curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json', 'Authorization: OAuth '.$accessToken));

			$response = curl_exec($curlObj);
			
			$httpCode = curl_getinfo($curlObj, CURLINFO_HTTP_CODE); 
			
			curl_close($curlObj);

			return array("http_code" => $httpCode, "response" => $response);
		}
		
		public function getCorrectBlog($type)
		{
			$res = null;
			
			switch($type)
			{
				case "news_fr":
					$res = "ActiviteParanormale";
					break;
				case "news_en":
					$res = "Wakonda666";
					break;
				case "news_es":
					$res = "Amatukami";
					break;
				case "magic_fr":
					$res = "PrieresEtSortileges";
					break;
				case "magic_en":
					$res = "BookOfLucifer";
					break;
				case "catholicism_fr":
					$res = "JakinEtBoaz";
					break;
				case "catholicism_en":
					$res = "TheTempleOfZebuleon";
					break;
				case "magic_es":
					$res = "ElGrimorioDeAstaroth";
					break;
				case "test_en":
				case "test_es":
				case "test_fr":
					$res = "Test";
					break;
			}
			
			return $res;
		}

		public function getTypes()
		{
			return ["news_en", "news_es", "news_fr", "magic_fr", "magic_en", "catholicism_fr", "catholicism_en", "magic_es", "test_en", "test_fr", "test_es"];
		}
	}