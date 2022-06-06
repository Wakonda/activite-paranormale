<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;

	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\NewsVote;

	class APVoteExtension extends AbstractExtension
	{
		private $em;
		
		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
		}
		
		public function getFilters()
		{
			return array(
				new TwigFilter('average_rating_by_news', [$this, 'averageRatingByNewsFilter'])
			);
		}

		// Filters
		public function averageRatingByNewsFilter($entity)
		{
			return $this->em->getRepository(NewsVote::class)->getAverageVotesByArticle($entity);
		}

		public function getName()
		{
			return 'ap_voteextension';
		}
	}