<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * BookEditionBiographyRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BookEditionBiographyRepository extends EntityRepository
{
	public function getBookEditionByBiography($biography)
	{
		$qb = $this->createQueryBuilder("mb");
		
		$qb->where("mb.biography = :biography")
		   ->setParameter("biography", $biography)
		   ->andWhere("mb.bookEdition IS NOT NULL");
		   
		return $qb->getQuery()->getResult();
	}
}