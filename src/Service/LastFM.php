<?php

namespace App\Service;

class LastFM {
	private $apiKey = null;

	public function __construct() {
		$this->apiKey = $_ENV["LASTFM_API_KEY"];
	}

	private function lastfmRequest(array $params) {
		$params = array_merge([
			"api_key" => $this->apiKey,
			"format" => "json"
		], $params);

		$url = "https://ws.audioscrobbler.com/2.0/?" . http_build_query($params);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			throw new Exception("cURL error: " . curl_error($ch));
		}

		curl_close($ch);

		return json_decode($response, true);
	}

	private function albumGetInfo($mbid) {
		$data = $this->lastfmRequest([
			"method" => "album.getInfo",
			"mbid" => $mbid
		]);
		
		return $data;
	}

	private function secondsToHours(int $secondes): string
	{
		return sprintf(
			'%02d:%02d:%02d',
			floor($secondes / 3600),
			floor(($secondes % 3600) / 60),
			$secondes % 60
		);
	}

	public function getAlbumsByArtist($artist, $mbid) {
		$data = $this->lastfmRequest([
			"method" => "artist.gettopalbums",
			"artist" => $artist,
			"mbid" => $mbid,
			"limit" => 100
		]);

		$res = [];
		$albums = $data["topalbums"]["album"] ?? [];

		foreach ($albums as $album) {
			$data = [];
			$data["name"] = $album["name"];
			$data["id"] = isset($album["mbid"]) ? $album["mbid"] : null;
			$data["tracks"] = [];

			if(isset($album["mbid"])) {
				$tracks = $this->albumGetInfo($album["mbid"])["album"]["tracks"]["track"];

				foreach($tracks as $track) {
					$trackArray = [];
					$trackArray["name"] = $track["name"];
					$trackArray["duration"] = (!empty($d = $track["duration"]) ? $this->secondsToHours($d) : null);
					
					$data["tracks"][] = $trackArray;
				}
			}
			
			$res[] = $data;
		}

		return $res;
	}

	public function getAlbumTracks($albumId, $accessToken = null) {
		if(empty($accessToken))
			$accessToken = $this->getToken();
		
		$url = "https://api.spotify.com/v1/albums/$albumId/tracks";

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Authorization: Bearer $accessToken"
		]);

		$response = json_decode(curl_exec($ch));

		$tracks = [];

		foreach($response->items as $item) {
			$tracks[] = [
				"id" => $item->id,
				"name" => $item->name,
				"duration_ms" => $item->duration_ms
			];
		}

		return $tracks;
	}
}