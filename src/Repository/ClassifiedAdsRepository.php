<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ClassifiedAdsRepository
 */
class ClassifiedAdsRepository extends EntityRepository
{
		public function getClassifiedAds($locale)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->innerjoin("b.language", "l")
		   ->where("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $locale)
		   ->andWhere("b.archive = false");

		$qb->orderBy('b.writingDate', 'DESC');

		return $qb->getQuery();
	}
}