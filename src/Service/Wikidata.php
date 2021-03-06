<?php
	namespace App\Service;

	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\Biography;
	use App\Entity\Country;
	use App\Entity\Language;
	
	class Wikidata {
		private $em;
		
		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
		}

		public function getBiographyDatas(string $code, string $language): array
		{
			$res = [];
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

			$datas = json_decode($content);

			$res["title"] = $datas->entities->$code->labels->$language->value;
			$res["url"] = $datas->entities->$code->sitelinks->$languageWiki->url;
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			$birthDate = $datas->entities->$code->claims->P569[0]->mainsnak->datavalue->value->time;
			$birthDate = date_parse($birthDate);
			
			$res["socialNetwork"] = ["twitter" => null];

			if(property_exists($datas->entities->$code->claims, "P2002")) {
				$res["socialNetwork"]["twitter"] = "https://twitter.com/i/user/".$datas->entities->$code->claims->P2002[0]->qualifiers->P6552[0]->datavalue->value;
			}
			
			$res["birthDate"] = [
				"year" => $birthDate["year"],
				"month" => $birthDate["month"],
				"day" => $birthDate["day"]
			];

			$res["deathDate"] = [
				"year" => null,
				"month" => null,
				"day" => null
			];
			
			if(property_exists($datas->entities->$code->claims, "P570")) {
				$deathDate = $datas->entities->$code->claims->P570[0]->mainsnak->datavalue->value->time;
				$deathDate = date_parse($deathDate);
				
				$res["deathDate"] = [
					"year" => $deathDate["year"],
					"month" => $deathDate["month"],
					"day" => $deathDate["day"]
				];
			}
			
			if(property_exists($datas->entities->$code->claims, "P27")) {
				$country = $datas->entities->$code->claims->P27;

				$countryId = $country[0]->mainsnak->datavalue->value->id;
				$res["nationality"] = $this->getCountry($datas, $code, "P27", $language);
			}

			return $res;
		}
		
		public function getAlbumDatas(string $code, string $language)
		{
			$res = [];
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

			$datas = json_decode($content);

			$res["title"] = $datas->entities->$code->labels->$language->value;
			$res["url"] = $datas->entities->$code->sitelinks->$languageWiki->url;
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);
			
			// Songs
			$songArray = [];
			
			if(property_exists($songs = $datas->entities->$code->claims, "P658")) {
				foreach($songs->P658 as $song) {
					$idSong = $song->mainsnak->datavalue->value->id;

					$contentSong = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$idSong}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");
					$dataSong = json_decode($contentSong);
					
					$songArray[$idSong] = $dataSong->entities->$idSong->labels->$language->value;
				}
			}
				
			$res["tracklist"] = $songArray;

			$res["publicationDate"] = [
				"year" => null,
				"month" => null,
				"day" => null
			];
			
			if(property_exists($datas->entities->$code->claims, "P577")) {
				$publicationDate = $datas->entities->$code->claims->P577[0]->mainsnak->datavalue->value->time;
				$publicationDate = date_parse($publicationDate);
				
				$res["publicationDate"] = [
					"year" => $publicationDate["year"],
					"month" => $publicationDate["month"],
					"day" => $publicationDate["day"]
				];
			}

			return $res;
		}
		
		public function getArtistDatas(string $code, string $language): array
		{
			$res = [];
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

			$datas = json_decode($content);

			$res["title"] = $datas->entities->$code->labels->$language->value;
			$res["url"] = $datas->entities->$code->sitelinks->$languageWiki->url;
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages=${language}&ids=${code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			// country of origin
			$country = $datas->entities->$code->claims->P495;
			$countryId = $country[0]->mainsnak->datavalue->value->id;
			$res["origin"] = $this->getCountry($datas, $code, "P495");
			
			// website
			$websitesArray = [];
			$this->getIdsByProperty("P856", $datas, $code, "website", $language, $websitesArray);
			$res["links"] = $websitesArray;
			
			$personArray = [];
			
			$this->getIdsByProperty("P527", $datas, $code, "member", $language, $personArray);
			// dd($personArray);
			foreach($datas->entities->$code->claims->P527 as $member) {
				$start = null;
				$end = null;
				
				if(property_exists($member, "qualifiers")) {
					if(property_exists($member->qualifiers, "P580"))
						$start = $member->qualifiers->P580[0]->datavalue->value->time;
					if(property_exists($member->qualifiers, "P582"))
						$end = $member->qualifiers->P582[0]->datavalue->value->time;
				}
				
				$personArray["member"][$member->mainsnak->datavalue->value->id] = ["title" =>$personArray["member"][$member->mainsnak->datavalue->value->id]["title"], "objects" =>$personArray["member"][$member->mainsnak->datavalue->value->id]["objects"], "start" => $start, "end" => $end];
			}
			
			$res["person"] = $personArray;
			
			return $res;
		}
		
		public function getMovieDatas(string $code, string $language): array
		{
			$res = [];
			$languageWiki = $language."wiki";
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

			$datas = json_decode($content);

			$res["title"] = $datas->entities->$code->labels->$language->value;
			$res["url"] = $datas->entities->$code->sitelinks->$languageWiki->url;
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages=${language}&ids=${code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);
			
			if(property_exists($datas->entities->$code->claims, "P345")) {
				$value = $datas->entities->$code->claims->P345[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => "IMDb ID",
					"value" => $value
				];
			}
			
			if(property_exists($datas->entities->$code->claims, "P1258")) {
				$value = $datas->entities->$code->claims->P1258[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => "Rotten Tomatoes ID",
					"value" => $value
				];
			}
			
			if(property_exists($datas->entities->$code->claims, "P6127")) {
				$value = $datas->entities->$code->claims->P6127[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => "Letterboxd film ID",
					"value" => $value
				];
			}

			$personArray = [];

			// R??alisateur / Filmmaker / director
			$this->getIdsByProperty("P57", $datas, $code, "director", $language, $personArray);

			// Sc??naristes / Writer / screenwriter
			$this->getIdsByProperty("P58", $datas, $code, "screenwriter", $language, $personArray);

			// Acteurs / Actor / cast member
			$this->getIdsByProperty("P161", $datas, $code, "actor", $language, $personArray);

			// Producteur ex??cutif / executive producer
			$this->getIdsByProperty("P1431", $datas, $code, "executiveProducer", $language, $personArray);

			// Directeur de la photographie / director of photography
			$this->getIdsByProperty("P344", $datas, $code, "directorOfPhotography", $language, $personArray);

			// Monteur / film editor
			$this->getIdsByProperty("P1040", $datas, $code, "filmEditor", $language, $personArray);

			// costumier / costume designer
			$this->getIdsByProperty("P2515", $datas, $code, "costumDesigner", $language, $personArray);

			// compositeur / composer
			$this->getIdsByProperty("P86", $datas, $code, "composer", $language, $personArray);

			// producteur / producer
			$this->getIdsByProperty("P162", $datas, $code, "producer", $language, $personArray);
// die;
			$res["person"] = $personArray;

			// Soci??t??s de production / production company
			// $productionCompanies = $datas->entities->$code->claims->P272;

			if(property_exists($datas->entities->$code->claims, "P577")) {
				$publicationDate = $datas->entities->$code->claims->P577[0]->mainsnak->datavalue->value->time;
				$publicationDate = date_parse($publicationDate);
				
				$res["publicationDate"] = [
					"year" => $publicationDate["year"],
					"month" => $publicationDate["month"],
					"day" => $publicationDate["day"]
				];
			}

			if(property_exists($datas->entities->$code->claims, "P2047")) {
				$duration = $datas->entities->$code->claims->P2047;
				
				$res["duration"] = [
					"amount" => intval($duration[0]->mainsnak->datavalue->value->amount),
					"unit" => $this->getUnit($duration[0]->mainsnak->datavalue->value->unit)
				];
			}

			// country of origin
			if(property_exists($datas->entities->$code->claims, "P495")) {
				$country = $datas->entities->$code->claims->P495;
				$countryId = $country[0]->mainsnak->datavalue->value->id;
				$res["origin"] = $this->getCountry($datas, $code, "P495", $language);
			}

			// review score
			if(property_exists($datas->entities->$code->claims, "P444")) {
				$reviewScores = $datas->entities->$code->claims->P444;
				$reviewScoreArray = [];
				foreach($reviewScores as $reviewScore) {
					$reviewScoreArray[] = [
						"score" => $reviewScore->mainsnak->datavalue->value,
						"source" => $this->getPropertyValue($reviewScore->qualifiers->P447[0]->datavalue->value->id),
					];
				}

				$res["reviewScores"] = $reviewScoreArray;
			}

			// box office
			if(property_exists($datas->entities->$code->claims, "P2142")) {
				$boxOffice = $datas->entities->$code->claims->P2142;
				
				$res["boxOffice"] = [
					"amount" => intval($boxOffice[0]->mainsnak->datavalue->value->amount),
					"unit" => $this->getCurrencyIso4217($boxOffice[0]->mainsnak->datavalue->value->unit)
				];
			}

			// cost
			if(property_exists($datas->entities->$code->claims, "P2130")) {
				$cost = $datas->entities->$code->claims->P2130;
				
				$res["cost"] = [
					"amount" => $cost[0]->mainsnak->datavalue->value->amount,
					"unit" => $this->getCurrencyIso4217($cost[0]->mainsnak->datavalue->value->unit)
				];
			}

			// website
			if(property_exists($datas->entities->$code->claims, "P856")) {
				$websites = $datas->entities->$code->claims->P856;
				$websitesArray = [];
				$this->getIdsByProperty("P856", $datas, $code, "website", $language, $websitesArray);
				$res["websites"] = $websitesArray;
			}

			return $res;
		}
		
		public function getEpisodeTelevisionSerieDatas(string $code, string $language)
		{
			$res = [];
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

			$datas = json_decode($content);

			$res["title"] = $datas->entities->$code->labels->$language->value;
			$res["url"] = $datas->entities->$code->sitelinks->$languageWiki->url;
			
			return $res;
		}

		public function getTelevisionSerieDatas(string $code, string $language)
		{
			$res = [];
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

			$datas = json_decode($content);

			$res["title"] = $datas->entities->$code->labels->$language->value;
			$res["url"] = $datas->entities->$code->sitelinks->$languageWiki->url;

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			if(property_exists($datas->entities->$code->claims, "P495")) {
				$country = $datas->entities->$code->claims->P495;
				$countryId = $country[0]->mainsnak->datavalue->value->id;
				$res["origin"] = $this->getCountry($datas, $code, "P495");
			}

			// Episodes
			$episodeArray = [];
			
			$i = 1;
			
			if(property_exists($seasons = $datas->entities->$code->claims, "P527")) {
				foreach($seasons->P527 as $season) {
					$idSeason = $season->mainsnak->datavalue->value->id;

					$contentEpisode = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$idSeason}&sitefilter=${languageWiki}");
					$dataEpisode = json_decode($contentEpisode);
					
					foreach($dataEpisode->entities->$idSeason->claims->P527 as $e) {
						$idEpisode = $e->mainsnak->datavalue->value->id;
						$contentEpisodeDetail = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$idEpisode}&sitefilter=${languageWiki}");
						$contentEpisodeDetailContent = json_decode($contentEpisodeDetail);

						if(property_exists($contentEpisodeDetailContent->entities->$idEpisode->labels, $language)) {
							$date = null;	

							if(property_exists($contentEpisodeDetailContent->entities->$idEpisode->claims, "P577")) {
								$publicationDate = date_parse($contentEpisodeDetailContent->entities->$idEpisode->claims->P577[0]->mainsnak->datavalue->value->time);

								$date = [
									"year" => $publicationDate["year"],
									"month" => $publicationDate["month"],
									"day" => $publicationDate["day"]
								];
							}

							$episodeArray[$i][] = ["title" => $contentEpisodeDetailContent->entities->$idEpisode->labels->$language->value, "date" => $date, "wikidata" => $idEpisode];
						}
					}
					$i++;
				}
				
				$res["episodes"] = $episodeArray;
			}

			return $res;
		}

		private function getCurrencyIso4217(string $url, string $language = "en"): ?string {
			$id = array_reverse(explode("/", $url))[0];

			$data = json_decode(file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&ids=${id}&languages=${language}&format=json"));

			return $data->entities->$id->claims->P498[0]->mainsnak->datavalue->value;
		}

		private function getPageTitle(string $id, string $language) {
			$data = json_decode(file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&props=labels&ids=${id}&languages=${language}&format=json"));

			return (property_exists($data->entities->$id->labels, $language)) ? $data->entities->$id->labels->$language->value : null;
		}

		private function getIdsByProperty(string $property, $datas, $id, string $key, string $language, array &$personArray): void {
			if(property_exists($datas->entities->$id->claims, $property)) {
				foreach($datas->entities->$id->claims->$property as $data) {
					$singleArrayForCategory = array_reduce($personArray, 'array_merge', []);
					
					$value = $data->mainsnak->datavalue->value;
					$idValue = $value;

					switch(gettype($data->mainsnak->datavalue->value)) {
						case "object":
							$value = $data->mainsnak->datavalue->value->id;
							$idValue = $data->mainsnak->datavalue->value->id;
							
							if(isset($singleArrayForCategory[$value]))
								$value = $singleArrayForCategory[$value]["title"];
							else {
								$value = $this->getPageTitle($value, $language);
							}
						break;
					}

					if(!empty($value))
						$personArray[$key][$idValue] = ["title" => $value, "objects" => $this->em->getRepository(Biography::class)->getBiographyByWikidataOrTitle($value, $idValue)];
				}
			}
		}

		private function getUnit(string $url, string $language = "en"): ?string {
			$id = array_reverse(explode("/", $url))[0];

			return $this->getPropertyValue($id, $language);
		}

		private function getPropertyValue(string $id, string $language = "en"): ?string {
			$data = json_decode(file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&props=labels&ids=${id}&languages=${language}&format=json"));

			return $data->entities->$id->labels->$language->value;
		}

		private function getCountry($datas, string $code, string $countryId, string $language = "en", string $key = "country"): array {
			$res = [];

			if(property_exists($datas->entities->$code->claims, $countryId)) {
				$idCountry = $datas->entities->$code->claims->$countryId[0]->mainsnak->datavalue->value->id;

				$contentCountry = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages=${language}&ids=${idCountry}");
				$datasCountry = json_decode($contentCountry);

				$res[$key] = [
					"alpha2" => $datasCountry->entities->$idCountry->claims->P297[0]->mainsnak->datavalue->value,
					"alpga3" => $datasCountry->entities->$idCountry->claims->P298[0]->mainsnak->datavalue->value,
					"id" => $this->countryToEntity($datasCountry->entities->$idCountry->claims->P297[0]->mainsnak->datavalue->value, $language)->getId()
				];
			}
			
			return $res;
		}
		
		private function countryToEntity(string $code, string $language) {
			$language = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => $language]);
			return $this->em->getRepository(Country::class)->findOneBy(["internationalName" => strtolower($code), "language" => $language]);
		}
	}