<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFunction;
	
	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\ArtistBiography;
	use App\Entity\MusicBiography;
	use App\Entity\Music;
	use App\Entity\MusicGenre;

	class APMusicExtension extends AbstractExtension
	{
		private $em;
		
		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
		}
		
		public function getFilters()
		{
			return [];
		}

		public function getFunctions()
		{
			return array(
				new TwigFunction('artist_by_biography', [$this, 'getArtistsByBiography'], ['is_safe' => null]),
				new TwigFunction('music_by_biography', [$this, 'getMusicsByBiography'], ['is_safe' => null]),
				new TwigFunction('biography_by_artist', [$this, 'getBiographiesByArtist'], ['is_safe' => null]),
				new TwigFunction('biography_by_music', [$this, 'getBiographiesByMusic'], ['is_safe' => null]),
				new TwigFunction('music_by_album', [$this, 'getMusicsByAlbum'], ['is_safe' => null]),
				new TwigFunction('music_genres', [$this, 'getAllGenresByLocale'], ['is_safe' => null])
			);
		}

		public function getArtistsByBiography($biography)
		{
			return $this->em->getRepository(ArtistBiography::class)->getArtistsByBiography($biography);
		}

		public function getMusicsByBiography($biography)
		{
			return $this->em->getRepository(MusicBiography::class)->getMusicsByBiography($biography);
		}

		public function getBiographiesByArtist($artist)
		{
			return $this->em->getRepository(ArtistBiography::class)->getBiographiesByArtist($artist);
		}

		public function getBiographiesByMusic($music)
		{
			return $this->em->getRepository(MusicBiography::class)->getBiographiesByMusic($music);
		}

		public function getMusicsByAlbum($album)
		{
			return $this->em->getRepository(Music::class)->findBy(["album" => $album]);
		}
		
		public function getAllGenresByLocale($locale) {
			return $this->em->getRepository(MusicGenre::class)->getAllGenresByLocale($locale);
		}
	}