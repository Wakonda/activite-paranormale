<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
	
class PHPBB {
	private $PHPBB_URL = null;
	private $PHPBB_USERNAME = null;
	private $PHPBB_PASSWORD = null;
	
	private $em;
	
	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	public function getJWT(string $language): ?string
	{
		$this->setLanguage($language);

		$url = $this->PHPBB_URL."/login";

		$ch = curl_init();

		$data = ["username" => $this->PHPBB_USERNAME, "password" => $this->PHPBB_PASSWORD];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

		$result = curl_exec($ch);
		// if (curl_errno($ch)) {
			// echo 'Error:' . curl_error($ch);
		// }
		curl_close($ch);

		$result = json_decode($result);
		
		return $result->jwt;
	}
	
	public function checkUserExists(?string $token, ?string $username): bool
	{
		if(empty($token) or empty($username))
			return false;

		$curl = curl_init();

		$url = $this->PHPBB_URL."/get_account/".$username;

		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept-Type: application/json", "Authorization: Bearer {$token}"));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	 
		
		$json_response = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$errors = curl_error($curl);
		curl_close($curl);

		return Response::HTTP_OK == $code;
	}
	
	public function saveUser(string $token, string $username, string $password, string $email): array
	{
		$ch = curl_init();
		
		$url = $this->PHPBB_URL."/save";

		$data = [
			"username" => $username,
			"password" => $password,
			"email" => $email
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept-Type: application/json", "Authorization: Bearer {$token}"));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$json_response = curl_exec($ch);
		$errors = curl_error($ch);
		curl_close($ch);

		return json_decode($json_response, true);
	}

	public function setLanguage($language)
	{
		switch($language)
		{
			case "fr":
				$this->PHPBB_URL = $_ENV["PHPBB_FR_URL"];
				$this->PHPBB_USERNAME = $_ENV["PHPBB_FR_USERNAME"];
				$this->PHPBB_PASSWORD = $_ENV["PHPBB_FR_PASSWORD"];
				break;
			case "en":
				$this->PHPBB_URL = $_ENV["PHPBB_EN_URL"];
				$this->PHPBB_USERNAME = $_ENV["PHPBB_EN_USERNAME"];
				$this->PHPBB_PASSWORD = $_ENV["PHPBB_EN_PASSWORD"];
				break;
			case "es":
				$this->PHPBB_URL = $_ENV["PHPBB_ES_URL"];
				$this->PHPBB_USERNAME = $_ENV["PHPBB_ES_USERNAME"];
				$this->PHPBB_PASSWORD = $_ENV["PHPBB_ES_PASSWORD"];
				break;
		}
	}
	
	public function getForumsByUser(string $username) {
		$res = [];
		
		foreach($this->getLanguages() as $language) {
			$userExists = false;
			
			$url = isset($_ENV["PHPBB_".strtoupper($language)."_URL"]) ? $_ENV["PHPBB_".strtoupper($language)."_URL"] : null;

			if(!empty($url) and isset($_ENV["PHPBB_".strtoupper($language)."_USERNAME"]) and isset($_ENV["PHPBB_".strtoupper($language)."_PASSWORD"])) {
				$this->setLanguage($language);
				$token = $this->getJWT($language);
				$userExists = $this->checkUserExists($token, $username);
			}
			$res[] = [
				"url" => $url,
				"language" => strtoupper($language),
			    "exists" => $userExists
			];
		}

		return $res;
	}

	public function getLanguages()
	{
		return ["fr", "en", "es"];
	}
}