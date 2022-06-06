<?php

	namespace App\Service;
	
	use Symfony\Component\HttpFoundation\Response;

	class Shopify
	{
		private $scopes = ["read_content", "write_content"];
		private $shop = "activite-paranormale";
		private $version = "2020-07";
		
		private $blogId_array = array(
			"PrieresEtSortileges" => "50225512531",
			"ActiviteParanormale" => "50232131667"
		);
		
		private $slugName_array = array(
			"PrieresEtSortileges" => "magie-et-rituel",
			"ActiviteParanormale" => "paranormal"
		);
		
		public function getCode($redirect_uri)
		{
			$loginUrl = "https://" . $this->shop . ".myshopify.com/admin/oauth/authorize?client_id=" . $_ENV["SHOPIFY_API_KEY"] . "&scope=" . implode(",", $this->scopes) . "&redirect_uri=" . urlencode($redirect_uri);

			header("Location: ".$loginUrl);
			die;
		}
		
		public function addPost($blogTitle, $params, $title, $text, $img, $tags, $publicationDate, $author)
		{
			$hmac = $params->get('hmac');

			$params = array_diff_key($params->all(), ['hmac' => '']); // Remove hmac from params
			ksort($params); // Sort params lexographically

			$computed_hmac = hash_hmac('sha256', http_build_query($params), $_ENV["SHOPIFY_SECRET_API_KEY"]);

			$blogId = $this->blogId_array[$blogTitle];

			// Use hmac data to check that the response is from Shopify or not
			if (hash_equals($hmac, $computed_hmac)) {

				// Set variables for our request
				$query = array(
					"client_id" => $_ENV["SHOPIFY_API_KEY"], // Your API key
					"client_secret" => $_ENV["SHOPIFY_SECRET_API_KEY"], // Your app credentials (secret key)
					"code" => $params['code'] // Grab the access key from the URL
				);

				// Generate access token URL
				$access_token_url = "https://" . $this->shop . ".myshopify.com/admin/oauth/access_token";

				// Configure curl client and execute request
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $access_token_url);
				curl_setopt($ch, CURLOPT_POST, count($query));
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				$result = curl_exec($ch);

				curl_close($ch);

				// Store the access token
				$result = json_decode($result, true);
				
				$access_token = $result['access_token'];

				if(!empty($access_token)) {
					// Post an article to blog
					$url = "https://" . $this->shop . ".myshopify.com/admin/api/".$this->version."/blogs/".$blogId."/articles.json";

					$data = array(
						'title' => $title,
						'author' => $author,
						'tags' => implode(",", json_decode($tags)),
						'body_html' => $text,
						'published_at' => $publicationDate->format("D M j H:i:s \U\T\C Y")
						
					);

					if(!empty($img)) {
						$data = array_merge($data, 
						[
							"image" => 
							[
								"src" => $img,
								"alt" => $title
							]
						]);
					}
					
					$payload = json_encode(array("article" => $data));
					
					$headers = [
						"X-Shopify-Access-Token: ".$access_token,
						"Content-Type: application/json"
					];

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_POST, strlen($payload));
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					$result = curl_exec($ch);

					$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);

					$handle = null;
					$response = json_decode($result, true);
					
					if(isset($response["article"]) and isset($response["article"]["handle"]))
						$handle = $response["article"]["handle"];

					return ["http_code" => $httpcode, "handle" => $handle];
				}
			} else {
				return ["http_code" => Response::HTTP_BAD_REQUEST];
			}
		}
		
		public function getCorrectBlog($type)
		{
			$res = null;
			
			switch($type)
			{
				case "news_fr":
					$res = "ActiviteParanormale";
					break;
				case "magic_fr":
					$res = "PrieresEtSortileges";
					break;
			}
			
			return $res;
		}

		public function getTypes()
		{
			return ["news_fr", "magic_fr"];
		}

		public function getArticleUrl($blogTitle, $handle)
		{
			return "https://" . $this->shop . ".myshopify.com/blogs/".$this->slugName_array[$blogTitle]."/".$handle;
		}
	}