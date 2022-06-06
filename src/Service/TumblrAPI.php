<?php

	namespace App\Service;

	use Tumblr\API\Client;
	
	class TumblrAPI
	{
		private $blogName = "activite-paranormale";
		
		public function connect()
		{
			$client = new \Tumblr\API\Client(getenv("TUMBLR_CONSUMER_KEY"), getenv("TUMBLR_CONSUMER_SECRET"));
			$requestHandler = $client->getRequestHandler();
			$requestHandler->setBaseUrl('https://www.tumblr.com/');
			
			if (!isset($_GET['oauth_verifier']) or !$_GET['oauth_verifier']) {
				// grab the oauth token
				$resp = $requestHandler->request('POST', 'oauth/request_token', array());
				$out = $result = $resp->body;
				$data = array();
				parse_str($out, $data);

				// tell the user where to go
				// header("Location: ".'https://www.tumblr.com/oauth/authorize?oauth_token=' . $data['oauth_token']);
				echo "<script type='text/javascript'>  window.location='". 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $data['oauth_token']."'; </script>";
				
				$_SESSION['t']=$data['oauth_token'];
				$_SESSION['s']=$data['oauth_token_secret'];
			}
		}
		
		public function addPost($title, $body, $tags = [])
		{
			$client = new \Tumblr\API\Client(getenv("TUMBLR_CONSUMER_KEY"), getenv("TUMBLR_CONSUMER_SECRET"));
			$requestHandler = $client->getRequestHandler();
			$requestHandler->setBaseUrl('https://www.tumblr.com/');

			// If we are visiting the first time
			if (isset($_GET['oauth_verifier']) and $_GET['oauth_verifier'])
			{
				$verifier = $_GET['oauth_verifier'];

				// use the stored tokens
				$client->setToken($_SESSION['t'], $_SESSION['s']);

				// to grab the access tokens
				$resp = $requestHandler->request('POST', 'oauth/access_token', array('oauth_verifier' => $verifier));
				$out = $result = $resp->body;
				$data = array();
				parse_str($out, $data);

				// and print out our new keys we got back
				$token = $data['oauth_token'];
				$secret = $data['oauth_token_secret'];

				// and prove we're in the money
				$client = new \Tumblr\API\Client(getenv("TUMBLR_CONSUMER_KEY"), getenv("TUMBLR_CONSUMER_SECRET"), $token, $secret);
				$info = $client->getUserInfo();

				$postData = array('title' => $title, 'body' => $body);
				
				if(!empty($tags))
					$postData["tags"] = $tags;
				
				$client->createPost($this->blogName, $postData);
			}
		}

		public function getLanguages()
		{
			return ["en"];
		}
	}