<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFunction;
	
	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\ArtistBiography;
	use App\Entity\Music;

	class APMusicExtension extends AbstractExtension
	{
		private $em;
		
		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
		}
		
		public function getFilters()
		{
			return array();
		}

		public function getFunctions()
		{
			return array(
				new TwigFunction('artist_by_biography', array($this, 'getArtistsByBiography'), array('is_safe' => null)),
				new TwigFunction('biography_by_artist', array($this, 'getBiographiesByArtist'), array('is_safe' => null)),
				new TwigFunction('music_by_album', array($this, 'getMusicsByAlbum'), array('is_safe' => null))
			);
		}

		public function getArtistsByBiography($biography)
		{
			return $this->em->getRepository(ArtistBiography::class)->getArtistsByBiography($biography);
		}

		public function getBiographiesByArtist($artist)
		{
			return $this->em->getRepository(ArtistBiography::class)->getBiographiesByArtist($artist);
		}

		public function getMusicsByAlbum($album)
		{
			return $this->em->getRepository(Music::class)->findBy(["album" => $album]);
		}
	}