<?php
	namespace App\Service;

	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\Biography;
	use App\Entity\Country;
	use App\Entity\Language;
	use App\Entity\Licence;
	use App\Service\Identifier;
	
	class Wikidata {
		private $em;
		
		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
		}
		
		public function getTitleAndUrl(string $code, string $language): array
		{
			$res = [];
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

			$datas = json_decode($content);
			
			if(!property_exists($datas->entities->$code->labels, $language))
				return [];

			$res["title"] = ucfirst($datas->entities->$code->labels->$language->value);
			$res["url"] = $this->getUrl($datas, $code, $languageWiki);
			
			return $res;
		}

		public function getBiographyDatas(string $code, string $language): array
		{
			$res = $this->getTitleAndUrl($code, $language);
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			$res["socialNetwork"] = ["twitter" => null, "youtube" => null, "facebook" => null, "instagram" => null];

			if(property_exists($datas->entities->$code->claims, "P2002"))
				$res["socialNetwork"]["twitter"] = "https://twitter.com/i/user/".$datas->entities->$code->claims->P2002[0]->qualifiers->P6552[0]->datavalue->value;
			
			if(property_exists($datas->entities->$code->claims, "P2397"))
				$res["socialNetwork"]["youtube"] = "https://www.youtube.com/channel/".$datas->entities->$code->claims->P2397[0]->mainsnak->datavalue->value;
			
			if(property_exists($datas->entities->$code->claims, "P7085"))
				$res["socialNetwork"]["facebook"] = "https://www.facebook.com/".$datas->entities->$code->claims->P7085[0]->mainsnak->datavalue->value;
			
			if(property_exists($datas->entities->$code->claims, "P2003"))
				$res["socialNetwork"]["instagram"] = "https://www.instagram.com/".$datas->entities->$code->claims->P2003[0]->mainsnak->datavalue->value;

			$birthDate = null;
			
			if(property_exists($datas->entities->$code->claims, "P569")) {
				$birthDate = $datas->entities->$code->claims->P569[0]->mainsnak->datavalue->value->time;
				$birthDate = date_parse($birthDate);
			}
			
			$res["birthDate"] = [
				"year" => !empty($birthDate) ? $birthDate["year"] : null,
				"month" => !empty($birthDate) ? $birthDate["month"] : null,
				"day" => !empty($birthDate) ? $birthDate["day"] : null
			];

			$res["deathDate"] = [
				"year" => null,
				"month" => null,
				"day" => null
			];

			$res["image"] = $this->getImage($datas, $code);

			if(property_exists($datas->entities->$code->claims, "P570")) {
				$deathDate = $datas->entities->$code->claims->P570[0]->mainsnak->datavalue->value->time;
				$deathDate = date_parse($deathDate);
				
				$res["deathDate"] = [
					"year" => $deathDate["year"],
					"month" => $deathDate["month"],
					"day" => $deathDate["day"]
				];
			}
			
			$res["nationality"]["country"]["id"] = null;
			
			if(property_exists($datas->entities->$code->claims, "P27")) {
				$country = $datas->entities->$code->claims->P27;

				$countryId = $country[0]->mainsnak->datavalue->value->id;
				$res["nationality"] = $this->getCountry($datas, $code, "P27", $language);
			}

			if(property_exists($datas->entities->$code->claims, "P345")) {
				$value = $datas->entities->$code->claims->P345[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::IMDB_ID,
					"value" => $value
				];
			}
			
			if(property_exists($datas->entities->$code->claims, "P434")) {
				$value = $datas->entities->$code->claims->P434[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::MUSICBRAINZ_ARTIST_ID,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P213")) {
				$value = $datas->entities->$code->claims->P213[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ISNI,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P214")) {
				$value = $datas->entities->$code->claims->P214[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::VIAF_ID,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P1989")) {
				$value = $datas->entities->$code->claims->P1989[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ENCYCLOPAEDIA_METALLUM_ARTIST_ID,
					"value" => $value
				];
			}

			return $res;
		}
		
		private function getImage($datas, $code, $codeImage = "P18"): array
		{
			$imageArray = ["url" => null, "source" => null, "user" => null, "license" => null, "description" => null];

			if(property_exists($datas->entities->$code->claims, $codeImage)) {
				$maxWidth = 500;
				$filename = urlencode($datas->entities->$code->claims->$codeImage[0]->mainsnak->datavalue->value);
				
				$image = json_decode(file_get_contents("https://www.mediawiki.org/w/api.php?action=query&titles=File:${filename}&prop=imageinfo&iilimit=50&iiurlwidth=${maxWidth}&iiprop=timestamp%7Cuser%7Curl|size|extmetadata&format=json"));
				$imageProperty = $image->query->pages->{'-1'}->imageinfo[0];
				$url = null;
				
				if($imageProperty->width > $maxWidth)
					$url = $imageProperty->thumburl;
				else
					$url = $imageProperty->url;
				
				$imageArray = ["url" => $url, 
							   "source" => $imageProperty->descriptionshorturl,
							   "user" => $imageProperty->user,
							   "license" => $imageProperty->extmetadata->LicenseShortName->value,
							   "description" => $imageProperty->extmetadata->Categories->value];
			}
			
			return $imageArray;
		}
		
		public function getGenericDatas(string $code, string $language)
		{
			$res = $this->getTitleAndUrl($code, $language);
			$languageWiki = $language."wiki";

			if(empty($res))
				return [];

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			$res["image"] = $this->getImage($datas, $code);

			return $res;
		}
		
		public function getAlbumDatas(string $code, string $language)
		{
			$res = $this->getTitleAndUrl($code, $language);
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			// Songs
			$songArray = [];

			if(property_exists($songs = $datas->entities->$code->claims, "P658")) {
				foreach($songs->P658 as $song) {
					$idSong = $song->mainsnak->datavalue->value->id;

					$songArray[$idSong] = $this->getMusicByIdSong($idSong, $language);
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

			if(property_exists($datas->entities->$code->claims, "P1729")) {
				$value = $datas->entities->$code->claims->P1729[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ALLMUSIC_ALBUM_ID,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P5749")) {
				$value = $datas->entities->$code->claims->P5749[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::AMAZON_STANDARD_IDENTIFICATION_NUMBER,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P436")) {
				$value = $datas->entities->$code->claims->P436[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::MUSICBRAINZ_RELEASE_GROUP_ID,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P2205")) {
				$value = $datas->entities->$code->claims->P2205[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::SPOTIFY_ALBUM_ID,
					"value" => $value
				];
			}

			return $res;
		}

		public function getMusicDatas(string $code, string $language)
		{
			$res = [];
			$languageWiki = $language."wiki";
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

			$datas = json_decode($content);

			$res["title"] = $datas->entities->$code->labels->$language->value;

			if(property_exists($datas->entities->$code->sitelinks, $languageWiki))
				$res["url"] = $this->getUrl($datas, $code, $languageWiki);

			$res = array_merge($res, $this->getMusicByIdSong($code, $language));

			if(isset($res["identifiers"])) {
				$identifiers = $res["identifiers"];
				$found_key = array_search(Identifier::YOUTUBE_VIDEO_ID, array_column($identifiers, 'identifier'));

				if(isset($identifiers[$found_key]) and $identifiers[$found_key]["identifier"] == Identifier::YOUTUBE_VIDEO_ID and !empty($d = $identifiers[$found_key]["value"])) {
					$res["embeddedCode"] = '<iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/'.$d.'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
				}
			}

			return $res;
		}

		private function getMusicByIdSong(string $idSong, string $language): array
		{
			$languageWiki = $language."wiki";
			$contentSong = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$idSong}&sitefilter=${languageWiki}");
			$dataSong = json_decode($contentSong);

			$res = [];

			$res["title"] = $dataSong->entities->$idSong->labels->$language->value;
			$res["wikidata"] = $idSong;

			// recording or performance of
			if(property_exists($dataSong->entities->$idSong->claims, "P2550")) {
				$id = $dataSong->entities->$idSong->claims->P2550[0]->mainsnak->datavalue->value->id;
				$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$id}&sitefilter=${languageWiki}&props=sitelinks%2Furls%7Caliases%7Cdescriptions%7Clabels");

				$datas = json_decode($content);

				$res["url"] = $this->getUrl($datas, $code, $languageWiki);
			}

			$duration = null;

			if(property_exists($dataSong->entities->$idSong->claims, "P2047")) {
				$duration = $dataSong->entities->$idSong->claims->P2047;
				$unit = $this->getUnit($duration[0]->mainsnak->datavalue->value->unit);
				$amount = intval($duration[0]->mainsnak->datavalue->value->amount);
			} elseif(property_exists($dataSong->entities->$idSong->claims, "P1651") and property_exists($dataSong->entities->$idSong->claims->P1651[0]->qualifiers, "P2047")) {
				$duration = $dataSong->entities->$idSong->claims->P1651[0]->qualifiers->P2047;
				$unit = $this->getUnit($duration[0]->datavalue->value->unit);
				$amount = intval($duration[0]->datavalue->value->amount);
			}

			if(!empty($duration)) {
				$unitString = null;

				if($unit == "second")
					$unitString = sprintf('%02d:%02d:%02d', ($amount/3600),($amount/60%60), $amount%60);

				$res["duration"] = [
					"amount" => $amount,
					"unit" => $unit,
					"unitString" => $unitString
				];
			}

			if(property_exists($dataSong->entities->$idSong->claims, "P1243")) {
				$value = $dataSong->entities->$idSong->claims->P1243[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ISRC,
					"value" => $value
				];
			}

			if(property_exists($dataSong->entities->$idSong->claims, "P1730")) {
				$value = $dataSong->entities->$idSong->claims->P1730[0]->mainsnak->datavalue->value;

				$res["identifiers"][] = [
					"identifier" => Identifier::ALLMUSIC_SONG_ID,
					"value" => $value
				];
			}

			if(property_exists($dataSong->entities->$idSong->claims, "P4404")) {
				$value = $dataSong->entities->$idSong->claims->P4404[0]->mainsnak->datavalue->value;

				$res["identifiers"][] = [
					"identifier" => Identifier::MUSICBRAINZ_RECORDING_ID,
					"value" => $value
				];
			}

			if(property_exists($dataSong->entities->$idSong->claims, "P1651")) {
				$value = $dataSong->entities->$idSong->claims->P1651[0]->mainsnak->datavalue->value;
	
				$res["identifiers"][] = [
					"identifier" => Identifier::YOUTUBE_VIDEO_ID,
					"value" => $value
				];
			}
			
			return $res;
		}
		
		public function getArtistDatas(string $code, string $language): array
		{
			$res = $this->getTitleAndUrl($code, $language);
			$languageWiki = $language."wiki";
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages=${language}&ids=${code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			// country of origin
			$country = $datas->entities->$code->claims->P495;
			$countryId = $country[0]->mainsnak->datavalue->value->id;
			$res["origin"] = $this->getCountry($datas, $code, "P495", $language);
			
			// website
			$websitesArray = [];
			$this->getIdsByProperty("P856", $datas, $code, "website", $language, $websitesArray);
			
			$res["image"] = $this->getImage($datas, $code, "P154");

			$res["links"] = isset($websitesArray["website"]) ? key($websitesArray["website"]) : null;

			if(property_exists($datas->entities->$code->claims, "P1902")) {
				$value = $datas->entities->$code->claims->P1902[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::SPOTIFY_ARTIST_ID,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P434")) {
				$value = $datas->entities->$code->claims->P434[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::MUSICBRAINZ_ARTIST_ID,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P1728")) {
				$value = $datas->entities->$code->claims->P1728[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ALLMUSIC_ARTIST_ID,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P213")) {
				$value = $datas->entities->$code->claims->P213[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ISNI,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P214")) {
				$value = $datas->entities->$code->claims->P214[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::VIAF_ID,
					"value" => $value
				];
			}

			$personArray = [];
			
			$this->getIdsByProperty("P527", $datas, $code, "member", $language, $personArray);

			if(property_exists($datas->entities->$code->claims, "P527"))
			{
				foreach($datas->entities->$code->claims->P527 as $member) {
					$start = null;
					$end = null;
					
					if(property_exists($member, "qualifiers")) {
						if(property_exists($member->qualifiers, "P580"))
							$start = $member->qualifiers->P580[0]->datavalue->value->time;
						if(property_exists($member->qualifiers, "P582"))
							$end = $member->qualifiers->P582[0]->datavalue->value->time;
					}

					if(isset($personArray["member"][$member->mainsnak->datavalue->value->id]))
						$personArray["member"][$member->mainsnak->datavalue->value->id] = ["title" => $personArray["member"][$member->mainsnak->datavalue->value->id]["title"], "objects" =>$personArray["member"][$member->mainsnak->datavalue->value->id]["objects"], "start" => $start, "end" => $end];
				}
			}

			$res["person"] = $personArray;
			
			return $res;
		}
		
		public function getEventDatas(string $code, string $language): array
		{
			$res = $this->getTitleAndUrl($code, $language);
			
			if(empty($res))
				return [];
			
			$languageWiki = $language."wiki";
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages=${language}&ids=${code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);
			
			$res["image"] = $this->getImage($datas, $code);

			$dateFrom = null;

			if(property_exists($datas->entities->$code->claims, "P585")) {
				$dateFrom = $datas->entities->$code->claims->P585[0]->mainsnak->datavalue->value->time;
				$dateFrom = date_parse($dateFrom);
			}
			
			$res["dateFrom"] = [
				"year" => !empty($dateFrom) ? $dateFrom["year"] : null,
				"month" => !empty($dateFrom) ? $dateFrom["month"] : null,
				"day" => !empty($dateFrom) ? $dateFrom["day"] : null
			];

			$longitude = null;
			$latitude = null;

			if(property_exists($datas->entities->$code->claims, "P625")) {
				$longitude = $datas->entities->$code->claims->P625[0]->mainsnak->datavalue->value->longitude;
				$latitude = $datas->entities->$code->claims->P625[0]->mainsnak->datavalue->value->latitude;
			}
			
			$res["longitude"] = $longitude;
			$res["latitude"] = $latitude;

			return $res;
		}
		
		public function getWebDirectoryDatas(string $code, string $language): array
		{
			$res = $this->getTitleAndUrl($code, $language);
			$languageWiki = $language."wiki";
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages=${language}&ids=${code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);
			
			$res["image"] = $this->getImage($datas, $code, "P154");

			$res["socialNetwork"] = ["twitter" => null, "youtube" => null, "facebook" => null, "instagram" => null];

			if(property_exists($datas->entities->$code->claims, "P2002"))
				$res["socialNetwork"]["twitter"] = "https://twitter.com/i/user/".$datas->entities->$code->claims->P2002[0]->qualifiers->P6552[0]->datavalue->value;
			
			if(property_exists($datas->entities->$code->claims, "P9100"))
				$res["socialNetwork"]["github"] = "https://github.com/topics/".$datas->entities->$code->claims->P9100[0]->mainsnak->datavalue->value;
			
			if(property_exists($datas->entities->$code->claims, "P2013"))
				$res["socialNetwork"]["facebook"] = "https://www.facebook.com/".$datas->entities->$code->claims->P2013[0]->mainsnak->datavalue->value;
			
			if(property_exists($datas->entities->$code->claims, "P2003"))
				$res["socialNetwork"]["instagram"] = "https://www.instagram.com/".$datas->entities->$code->claims->P2003[0]->mainsnak->datavalue->value;
			
			$foundedDate = null;
			
			if(property_exists($datas->entities->$code->claims, "P571")) {
				$foundedDate = $datas->entities->$code->claims->P571[0]->mainsnak->datavalue->value->time;
				$foundedDate = date_parse($foundedDate);
			}
			
			$res["foundedDate"] = [
				"year" => !empty($foundedDate) ? $foundedDate["year"] : null,
				"month" => !empty($foundedDate) ? $foundedDate["month"] : null,
				"day" => !empty($foundedDate) ? $foundedDate["day"] : null
			];
			
			$defunctDate = null;
			
			if(property_exists($datas->entities->$code->claims, "P576")) {
				$defunctDate = $datas->entities->$code->claims->P576[0]->mainsnak->datavalue->value->time;
				$defunctDate = date_parse($defunctDate);
			}
				
			$res["defunctDate"] = [
				"year" => !empty($defunctDate) ? $defunctDate["year"] : null,
				"month" => !empty($defunctDate) ? $defunctDate["month"] : null,
				"day" => !empty($defunctDate) ? $defunctDate["day"] : null
			];
			
			$res["link"] = null;
			
			if(property_exists($datas->entities->$code->claims, "P856")) {
				$value = $datas->entities->$code->claims->P856[0]->mainsnak->datavalue->value;
				
				$res["link"] = $value;
			}
			
			$res["licence"] = null;
			
			if(property_exists($datas->entities->$code->claims, "P275")) {
				$id = $datas->entities->$code->claims->P275[0]->mainsnak->datavalue->value->id;
				$data = json_decode(file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&props=labels&ids=${id}&languages=${language}&format=json"));
				
				if(property_exists($data->entities, "Q18810333") and property_exists($data->entities->Q18810333->labels, $language)) {
					$languageEntity = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => $language]);
					$licence = $this->em->getRepository(Licence::class)->findOneBy(["title" => $data->entities->Q18810333->labels->$language->value, "language" => $languageEntity]);
					
					if(!empty($licence))
						$res["licence"] = $licence->getId();
				}
			}

			return $res;
		}
		
		public function getMovieDatas(string $code, string $language): array
		{
			$res = $this->getTitleAndUrl($code, $language);
			$languageWiki = $language."wiki";
			
			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages=${language}&ids=${code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);
			
			$res["image"] = $this->getImage($datas, $code);
			
			if(property_exists($datas->entities->$code->claims, "P345")) {
				$value = $datas->entities->$code->claims->P345[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::IMDB_ID,
					"value" => $value
				];
			}
			
			if(property_exists($datas->entities->$code->claims, "P1258")) {
				$value = $datas->entities->$code->claims->P1258[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ROTTEN_TOMATOES_ID,
					"value" => $value
				];
			}
			
			if(property_exists($datas->entities->$code->claims, "P6127")) {
				$value = $datas->entities->$code->claims->P6127[0]->mainsnak->datavalue->value;

				$res["identifiers"][] = [
					"identifier" => Identifier::LETTERBOXD_FILM_ID,
					"value" => $value
				];
			}

			$personArray = [];

			// Réalisateur / Filmmaker / director
			$this->getIdsByProperty("P57", $datas, $code, "director", $language, $personArray);

			// Scénaristes / Writer / screenwriter
			$this->getIdsByProperty("P58", $datas, $code, "screenwriter", $language, $personArray);

			// Acteurs / Actor / cast member
			$this->getIdsByProperty("P161", $datas, $code, "actor", $language, $personArray);

			// Producteur exécutif / executive producer
			$this->getIdsByProperty("P1431", $datas, $code, "executiveProducer", $language, $personArray);

			// Directeur de la photographie / director of photography
			$this->getIdsByProperty("P344", $datas, $code, "directorOfPhotography", $language, $personArray);

			// Monteur / film editor
			$this->getIdsByProperty("P1040", $datas, $code, "filmEditor", $language, $personArray);

			// costumier / costume designer
			$this->getIdsByProperty("P2515", $datas, $code, "costumeDesigner", $language, $personArray);

			// compositeur / composer
			$this->getIdsByProperty("P86", $datas, $code, "composer", $language, $personArray);

			// producteur / producer
			$this->getIdsByProperty("P162", $datas, $code, "producer", $language, $personArray);

			$res["person"] = $personArray;

			// Sociétés de production / production company
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
			$res = $this->getTitleAndUrl($code, $language);
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			if(property_exists($datas->entities->$code->claims, "P179")) {
				$res["episodeNumber"] = intval($datas->entities->$code->claims->P179[0]->qualifiers->P1545[0]->datavalue->value);
			}

			if(property_exists($datas->entities->$code->claims, "P4908")) {
				$idSeason = $datas->entities->$code->claims->P4908[0]->mainsnak->datavalue->value->id;
				
				$datasSeason = json_decode(file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$idSeason}&sitefilter=${languageWiki}"));

				$res["season"] = $datasSeason->entities->$idSeason->claims->P179[0]->qualifiers->P1545[0]->datavalue->value;
			}

			if(property_exists($datas->entities->$code->claims, "P345")) {
				$value = $datas->entities->$code->claims->P345[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::IMDB_ID,
					"value" => $value
				];
			}
			
			if(property_exists($datas->entities->$code->claims, "P1258")) {
				$value = $datas->entities->$code->claims->P1258[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ROTTEN_TOMATOES_ID,
					"value" => $value
				];
			}

			if(property_exists($datas->entities->$code->claims, "P577")) {
				$releaseDate = date_parse($datas->entities->$code->claims->P577[0]->mainsnak->datavalue->value->time);

				$res["releaseDate"] = [
					"year" => $releaseDate["year"],
					"month" => $releaseDate["month"],
					"day" => $releaseDate["day"]
				];
			}

			if(property_exists($datas->entities->$code->claims, "P2047")) {
				$duration = $datas->entities->$code->claims->P2047;

				$res["duration"] = [
					"amount" => intval($duration[0]->mainsnak->datavalue->value->amount),
					"unit" => $this->getUnit($duration[0]->mainsnak->datavalue->value->unit)
				];
			}

			return $res;
		}

		public function getTelevisionSerieDatas(string $code, string $language)
		{
			$res = $this->getTitleAndUrl($code, $language);
			$languageWiki = $language."wiki";

			$content = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages={$language}&ids={$code}&sitefilter=${languageWiki}");

			$datas = json_decode($content);

			if(property_exists($datas->entities->$code->claims, "P495")) {
				$country = $datas->entities->$code->claims->P495;
				$countryId = $country[0]->mainsnak->datavalue->value->id;
				$res["origin"] = $this->getCountry($datas, $code, "P495");
			}
			
			if(property_exists($datas->entities->$code->claims, "P345")) {
				$value = $datas->entities->$code->claims->P345[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::IMDB_ID,
					"value" => $value
				];
			}
			
			if(property_exists($datas->entities->$code->claims, "P1258")) {
				$value = $datas->entities->$code->claims->P1258[0]->mainsnak->datavalue->value;
				
				$res["identifiers"][] = [
					"identifier" => Identifier::ROTTEN_TOMATOES_ID,
					"value" => $value
				];
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
							
							$identifiers = [];

							if(property_exists($contentEpisodeDetailContent->entities->$idEpisode->claims, "P345")) {
								$value = $contentEpisodeDetailContent->entities->$idEpisode->claims->P345[0]->mainsnak->datavalue->value;
								
								$identifiers[] = [
									"identifier" => Identifier::IMDB_ID,
									"value" => $value
								];
							}

							if(property_exists($contentEpisodeDetailContent->entities->$idEpisode->claims, "P1258")) {
								$value = $contentEpisodeDetailContent->entities->$idEpisode->claims->P1258[0]->mainsnak->datavalue->value;
								
								$identifiers[] = [
									"identifier" => Identifier::ROTTEN_TOMATOES_ID,
									"value" => $value
								];
							}
							
							$durationArray = [];

							if(property_exists($contentEpisodeDetailContent->entities->$idEpisode->claims, "P2047")) {
								$duration = $contentEpisodeDetailContent->entities->$idEpisode->claims->P2047;

								$durationArray = [
									"amount" => intval($duration[0]->mainsnak->datavalue->value->amount),
									"unit" => $this->getUnit($duration[0]->mainsnak->datavalue->value->unit)
								];
							}

							$episodeArray[$i][] = ["title" => $contentEpisodeDetailContent->entities->$idEpisode->labels->$language->value, "date" => $date, "wikidata" => $idEpisode, "identifiers" => $identifiers, "duration" => $durationArray];
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
			$res[$key] = [
				"alpha2" => null,
				"alpga3" => null,
				"id" => null
			];

			if(property_exists($datas->entities->$code->claims, $countryId)) {
				$idCountry = $datas->entities->$code->claims->$countryId[0]->mainsnak->datavalue->value->id;

				$contentCountry = file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&languages=${language}&ids=${idCountry}");
				$datasCountry = json_decode($contentCountry);

				if(empty($datasCountry->entities->$idCountry->claims->P297[0]))
					return $res;

				$res[$key] = [
					"alpha2" => $datasCountry->entities->$idCountry->claims->P297[0]->mainsnak->datavalue->value,
					"alpga3" => $datasCountry->entities->$idCountry->claims->P298[0]->mainsnak->datavalue->value,
					"id" => !empty($c = $this->countryToEntity($datasCountry->entities->$idCountry->claims->P297[0]->mainsnak->datavalue->value, $language)) ? $c->getId() : null
				];
			}
			
			return $res;
		}
		
		private function countryToEntity(string $code, string $language) {
			$language = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => $language]);
			return $this->em->getRepository(Country::class)->findOneBy(["internationalName" => strtolower($code), "language" => $language]);
		}

		private function getUrl(object $datas, string $code, string $languageWiki): ?string {
			$siteLinks = $datas->entities->$code->sitelinks;
			
			if(property_exists($siteLinks, $languageWiki))
				return $siteLinks->$languageWiki->url;

			return null;
		}
	}