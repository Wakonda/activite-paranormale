<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;
	
	use App\Service\APUser;

	class APUserExtension extends AbstractExtension
	{
		private $apUser;
		
		public function __construct(APUser $apUser)
		{
			$this->apUser = $apUser;
		}
		
		public function getFilters()
		{
			return array(
				new TwigFilter('count_contributions_user', array($this, 'countContributionsByUserFilter')),
			);
		}

		public function countContributionsByUserFilter($user, $type)
		{
			$contributionsArray = $this->apUser->countContributionByUser($user);
			
			if($type == 'comment')
				$total = $contributionsArray['comment'];
			else
				$total = $contributionsArray['total'] - $contributionsArray['comment'];
			
			return $total;
		}

		public function getName()
		{
			return "ap_user_extension";
		}
	}