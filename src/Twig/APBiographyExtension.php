<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;
	use Twig\TwigFunction;
	
	use App\Entity\EntityLinkBiography;

	use Doctrine\ORM\EntityManagerInterface;

	class APBiographyExtension extends AbstractExtension
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
			return [
				new TwigFunction('occupations_by_biography', array($this, 'getOccupationsByBiography'))
			];
		}
		
		public function getOccupationsByBiography($biography): array
		{
			return $this->em->getRepository(EntityLinkBiography::class)->getOccupationsByBiography($biography);
		}
		
		public function getName()
		{
			return 'ap_mobileextension';
		}
	}