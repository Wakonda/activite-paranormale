<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFunction;
	
	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\Movies\MovieBiography;
	use App\Entity\Movies\Movie;
	use App\Entity\Movies\EpisodeTelevisionSerie;
	use App\Entity\Movies\TelevisionSerieBiography;

	class APMovieExtension extends AbstractExtension
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
				new TwigFunction('movie_biographies_by_occupation', array($this, 'getMovieBiographiesByOccupation'), array('is_safe' => null)),
				new TwigFunction('televisionserie_biographies_by_occupation', array($this, 'getTelevisionSerieBiographiesByOccupation'), array('is_safe' => null)),
				new TwigFunction('episode_televisionserie_biographies_by_occupation', array($this, 'getEpisodeTelevisionSerieBiographiesByOccupation'), array('is_safe' => null)),
				new TwigFunction('movies_by_biography', array($this, 'getMoviesByBiography'), array('is_safe' => null)),
				new TwigFunction('television_series_by_biography', array($this, 'getTelevisionSeriesByBiography'), array('is_safe' => null)),
				new TwigFunction('film_series', array($this, 'getFilmSeries'), array('is_safe' => null)),
				new TwigFunction('episodes_television_serie', array($this, 'getEpisodes'), array('is_safe' => null)),
				new TwigFunction('url_identifier', array($this, 'getURLIdentifier'), array('is_safe' => null))
			);
		}
		
		public function getURLIdentifier(string $id, string $value): string {
			return \App\Service\Identifier::getURLIdentifier($value)[$id];
		}

		public function getMovieBiographiesByOccupation($entity)
		{
			$data = [];
			
			foreach($entity->getMovieBiographies() as $bm)
				$data[$bm->getOccupation()][] = ["title" => $bm->getBiography()->getTitle(), "id" => $bm->getBiography()->getId(), "role" => $bm->getRole()];

			return $data;
		}

		public function getTelevisionSerieBiographiesByOccupation($entity)
		{
			$data = [];
			
			foreach($entity->getTelevisionSerieBiographies() as $bm)
				$data[$bm->getOccupation()][] = ["title" => $bm->getBiography()->getTitle(), "id" => $bm->getBiography()->getId(), "role" => $bm->getRole()];

			return $data;
		}

		public function getEpisodeTelevisionSerieBiographiesByOccupation($entity)
		{
			$data = [];
			
			foreach($entity->getEpisodeTelevisionSerieBiographies() as $bm)
				$data[$bm->getOccupation()][] = ["title" => $bm->getBiography()->getTitle(), "id" => $bm->getBiography()->getId(), "role" => $bm->getRole()];

			return $data;
		}

		public function getMoviesByBiography($biography)
		{
			return $this->em->getRepository(MovieBiography::class)->getMovieByBiography($biography);
		}

		public function getTelevisionSeriesByBiography($biography)
		{
			return $this->em->getRepository(TelevisionSerieBiography::class)->getTelevisionSerieByBiography($biography);
		}
		
		public function getFilmSeries($entity)
		{
			$first = $this->em->getRepository(Movie::class)->getFirstFilmSeries($entity);
		
			if(empty($first))
				return [];

			$movies = [];
			$movies[] = $first;
			
			return $this->em->getRepository(Movie::class)->getFilmSeries($first, $movies);
		}
		
		public function getEpisodes($televisionSerie)
		{
			return $this->em->getRepository(EpisodeTelevisionSerie::class)->getEpisodes($televisionSerie);
		}

		public function getName()
		{
			return 'ap_movie_extension';
		}
	}