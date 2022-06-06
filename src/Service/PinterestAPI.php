<?php

	namespace App\Service;

	use seregazhuk\PinterestBot\Factories\PinterestBot;
	
	class PinterestAPI
	{
		public function send($entity, $image, $url)
		{
			$pinterestBoards = [
				"Activité-Paranormale" => "fr",
				"Paranormal-Activity" => "en",
				"Actividad-Paranormal" => "es"
			];

			$bot = PinterestBot::create();
			$bot->auth->login(getenv("PINTEREST_EMAIL"), getenv("PINTEREST_PASSWORD"));
			
			$boards = $bot->boards->forUser(getenv("PINTEREST_USERNAME"));
			$i = 0;

			foreach($boards as $board) {
				if($pinterestBoards[$board["name"]] == $entity->getLanguage()->getAbbreviation()) {
					break;
				}
				$i++;
			}

			$bot->pins->create($image, $boards[$i]['id'], $entity->getTitle(), $url);

			$res = null;

			if(empty($bot->getLastError()))
				$res = "success";
			else
				$res = $bot->getLastError();

			return $res;
		}

		public function getLanguages()
		{
			return ["fr", "en", "es"];
		}
	}