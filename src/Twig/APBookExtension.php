<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFunction;
	
	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\Movies\MovieBiography;
	use App\Entity\Movies\Movie;
	use App\Entity\Movies\EpisodeTelevisionSerie;
	use App\Entity\Movies\TelevisionSerieBiography;

	class APBookExtension extends AbstractExtension
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
				new TwigFunction('book_edition_biographies_by_occupation', array($this, 'getBookEditionBiographiesByOccupation'), array('is_safe' => null))
			);
		}

		public function getBookEditionBiographiesByOccupation($entity)
		{
			$data = [];
			
			foreach($entity->getBiographies() as $bm)
				$data[$bm->getOccupation()][] = ["title" => $bm->getBiography()->getTitle(), "id" => $bm->getBiography()->getId(), "role" => $bm->getRole()];

			return $data;
		}

		public function getName()
		{
			return 'ap_book_extension';
		}
	}