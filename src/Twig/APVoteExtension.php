<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Vote;

class APVoteExtension extends AbstractExtension
{
	private $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}
	
	public function getFilters()
	{
		return [
			new TwigFilter('average_rating_by_news', [$this, 'averageRatingByNewsFilter'])
		];
	}

	// Filters
	public function averageRatingByNewsFilter($entity)
	{
		$parentEntityMetadata = $this->em->getClassMetadata(Vote::class);
		$subClasses = $parentEntityMetadata->subClasses;
		$className = null;

		foreach($subClasses as $subClass)
			if((new $subClass())->getMainEntityClassName() == get_class($entity))
				$className = $subClass;

		return $this->em->getRepository($className)->getAverageVotesByArticle($entity);
	}

	public function getName()
	{
		return 'ap_voteextension';
	}
}