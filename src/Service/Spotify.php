<?php

namespace App\Service;

class Spotify {
	private function getToken() {
		$SPOTIFY_CLIENT_ID="656bd1af54454a6180c9c40b67260853";
		$SPOTIFY_CLIENT_SECRET="f75f5b9150ff420aacedeb54547818c0";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
		  'Content-Type: application/x-www-form-urlencoded',
		  'Authorization: Basic ' . base64_encode($SPOTIFY_CLIENT_ID.":".$SPOTIFY_CLIENT_SECRET)
		]);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
		  'grant_type' => 'client_credentials'
		]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = json_decode(curl_exec($ch), true);
		curl_close($ch);

		return $result["access_token"];
	}

	public function getAlbumsByArtist($artistId) {
		$accessToken = $this->getToken();
		$url = "https://api.spotify.com/v1/artists/{$artistId}/albums?limit=50&offset=0";

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Authorization: Bearer $accessToken"
		]);

		$response = json_decode(curl_exec($ch));

		$albums = [];

		foreach($response->items as $item) {
			if($item->type == "album") {
				$albums[] = [
					"id" => $item->id,
					"name" => $item->name,
					"release_date" => $item->release_date,
					"tracks" => $this->getAlbumTracks($item->id, $accessToken)
				];
			}
		}

		return $albums;
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