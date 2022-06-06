<?php
	namespace App\Service;
	
	class GoogleBook
	{
		public function getBookInfoByISBN(string $isbn): array {
			$curlObj = curl_init();
			
			$key = getenv("BLOGGER_API_KEY");
			
			$isbn = str_replace("-", "", $isbn);
			
			$curl = curl_init();

			curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/books/v1/volumes?key='.$key.'&q=isbn:'.$isbn);
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlObj, CURLOPT_HEADER, 0);
			curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
			 
			$response = curl_exec($curlObj);
			
			curl_close($curl);
			
			return json_decode($response, true);
		}
	}