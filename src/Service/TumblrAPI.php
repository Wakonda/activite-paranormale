<?php

	namespace App\Service;

	use Tumblr\API\Client;
	
	class TumblrAPI
	{
		private $blogName = "iron-tv";
		
		public function connect()
		{
			$client = new \Tumblr\API\Client($_ENV["TUMBLR_CONSUMER_KEY"], $_ENV["TUMBLR_CONSUMER_SECRET"]);
			$requestHandler = $client->getRequestHandler();
			$requestHandler->setBaseUrl('https://www.tumblr.com/');
			
			if (!isset($_GET['oauth_verifier']) or !$_GET['oauth_verifier']) {
				$resp = $requestHandler->request('POST', 'oauth/request_token', array());
				$out = $result = $resp->body;
				$data = array();
				parse_str($out, $data);

				echo "<script>  window.location='". 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $data['oauth_token']."'; </script>";
				
				$_SESSION['t']=$data['oauth_token'];
				$_SESSION['s']=$data['oauth_token_secret'];
			}
		}
		
		public function addPost($title, $body, $tags = [])
		{
			$client = new \Tumblr\API\Client($_ENV["TUMBLR_CONSUMER_KEY"], $_ENV["TUMBLR_CONSUMER_SECRET"]);
			$requestHandler = $client->getRequestHandler();
			$requestHandler->setBaseUrl('https://www.tumblr.com/');

			if (isset($_GET['oauth_verifier']) and $_GET['oauth_verifier'])
			{
				$verifier = $_GET['oauth_verifier'];
				$client->setToken($_SESSION['t'], $_SESSION['s']);

				$resp = $requestHandler->request('POST', 'oauth/access_token', array('oauth_verifier' => $verifier));

				$out = $result = $resp->body;
				$data = array();
				parse_str($out, $data);

				$token = $data['oauth_token'];
				$secret = $data['oauth_token_secret'];

				$client = new \Tumblr\API\Client($_ENV["TUMBLR_CONSUMER_KEY"], $_ENV["TUMBLR_CONSUMER_SECRET"], $token, $secret);
				$info = $client->getUserInfo();

				$postData = array('title' => $title, 'body' => $body);

				if(!empty($tags))
					$postData["tags"] = $tags;

				$client->createPost($this->blogName, $postData);
			}
		}

		public function getTypes()
		{
			return ["music_fr"];
		}
	}