<?php
	namespace App\Service;
	
	class Identifier {
		const IMDB_ID = "IMDb ID";
		const ROTTEN_TOMATOES_ID = "Rotten Tomatoes ID";
		const LETTERBOXD_FILM_ID = "Letterboxd film ID";
		const SPOTIFY_ARTIST_ID = "Spotify artist ID";
		const SPOTIFY_ALBUM_ID = "Spotify album ID";
		const SPOTIFY_TRACK_ID = "Spotify track ID";
		const MUSICBRAINZ_ARTIST_ID = "MusicBrainz artist ID";
		const MUSICBRAINZ_RECORDING_ID = "MusicBrainz recording ID";
		const MUSICBRAINZ_RELEASE_GROUP_ID = "MusicBrainz release group ID";
		const ALLMUSIC_ARTIST_ID = "AllMusic artist ID";
		const ALLMUSIC_SONG_ID = "AllMusic song ID";
		const ALLMUSIC_ALBUM_ID = "AllMusic album ID";
		const ISNI = "ISNI";
		const VIAF_ID = "VIAF ID";
		const ISRC = "ISRC";
		const YOUTUBE_VIDEO_ID = "YouTube video ID";
		const ENCYCLOPAEDIA_METALLUM_ARTIST_ID = "Encyclopaedia Metallum artist ID";
		const AMAZON_STANDARD_IDENTIFICATION_NUMBER = "Amazon Standard Identification Number";

		public static function getMovieIdentifiers(): array
		{
			return [
				self::IMDB_ID, self::ROTTEN_TOMATOES_ID, self::LETTERBOXD_FILM_ID
			];
		}

		public static function getTelevisionSerieIdentifiers(): array
		{
			return [
				self::IMDB_ID, self::ROTTEN_TOMATOES_ID
			];
		}

		public static function getAlbumIdentifiers(): array
		{
			return [
				self::AMAZON_STANDARD_IDENTIFICATION_NUMBER, self::MUSICBRAINZ_RELEASE_GROUP_ID, self::ALLMUSIC_ALBUM_ID, self::SPOTIFY_ALBUM_ID
			];
		}

		public static function getArtistIdentifiers(): array
		{
			return [
				self::SPOTIFY_ARTIST_ID, self::MUSICBRAINZ_ARTIST_ID, self::ALLMUSIC_ARTIST_ID, self::ISNI, self::VIAF_ID
			];
		}

		public static function getMusicIdentifiers(): array
		{
			return [
				self::ISRC, self::ALLMUSIC_SONG_ID, self::MUSICBRAINZ_RECORDING_ID, self::YOUTUBE_VIDEO_ID
			];
		}

		public static function getBiographyIdentifiers(): array
		{
			return [
				self::MUSICBRAINZ_ARTIST_ID, self::IMDB_ID, self::ENCYCLOPAEDIA_METALLUM_ARTIST_ID, self::VIAF_ID, self::ISNI
			];
		}
		
		public static function getURLIdentifier(string $value): array {
			return [
				self::IMDB_ID => "https://www.imdb.com/title/${value}",
				self::ROTTEN_TOMATOES_ID => "https://www.rottentomatoes.com/m/${value}", 
				self::LETTERBOXD_FILM_ID => "https://letterboxd.com/film/${value}",
				self::ISRC => "https://isrcsearch.ifpi.org/#!/search?isrcCode=${value}&tab=lookup&showReleases=0&start=0&number=10",
				self::ALLMUSIC_SONG_ID => "https://www.allmusic.com/song/${value}",
				self::MUSICBRAINZ_RECORDING_ID => "https://musicbrainz.org/recording/${value}",
				self::AMAZON_STANDARD_IDENTIFICATION_NUMBER => "https://www.amazon.com/dp/${value}",
				self::MUSICBRAINZ_RELEASE_GROUP_ID => "https://musicbrainz.org/release-group/${value}",
				self::ALLMUSIC_ALBUM_ID => "https://www.allmusic.com/album/${value}",
				self::SPOTIFY_ALBUM_ID => "https://open.spotify.com/album/${value}",
				self::SPOTIFY_ARTIST_ID => "https://open.spotify.com/artist/${value}",
				self::SPOTIFY_TRACK_ID => "https://open.spotify.com/track/${value}",
				self::MUSICBRAINZ_ARTIST_ID => "https://musicbrainz.org/artist/${value}",
				self::ALLMUSIC_ARTIST_ID => "https://www.allmusic.com/artist/${value}",
				self::ISNI => "https://isni.org/isni/${value}",
				self::VIAF_ID => "https://viaf.org/viaf/${value}",
				self::YOUTUBE_VIDEO_ID => "https://www.youtube.com/watch?v=${value}",
				self::ENCYCLOPAEDIA_METALLUM_ARTIST_ID => "https://www.metal-archives.com/artists//${value}"
			];
		}
	}