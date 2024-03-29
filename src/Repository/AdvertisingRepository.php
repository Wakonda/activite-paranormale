<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * AdvertisingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AdvertisingRepository extends EntityRepository
{
	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()>getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 'c.width', 'c.height', 'c.id');

		$qb = $this->createQueryBuilder('c');
		$qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$orWhere = [];
			
			foreach($aColumns as $column)
				$orWhere[] = $column." LIKE :search";

			$qb->andWhere(implode(" OR ", $orWhere))
			   ->setParameter('search', $search);
		}
		if($count)
		{
			$qb->select("count(c)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else
			$qb->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);

		return $qb->getQuery()->getResult();
	}
	
	public function getOneRandomAdsByWidthAndHeight(int $maxWidth, int $maxHeight) {
		$qb = $this->createQueryBuilder("a");

		$qb->select("COUNT(a.id) AS countRow")
		   ->where("a.width <= :maxWidth")
		   ->andWhere("a.height <= :maxHeight")
		   ->andWhere("a.active = true")
		   ->setParameter("maxWidth", $maxWidth)
		   ->setParameter("maxHeight", $maxHeight);

		$max = max($qb->getQuery()->getSingleScalarResult() - 1, 0);
		$offset = rand(0, $max);
		
		$qb = $this->createQueryBuilder("a");
		
		$qb->where("a.width <= :maxWidth")
		   ->andWhere("a.height <= :maxHeight")
		   ->andWhere("a.active = true")
		   ->setParameter("maxWidth", $maxWidth)
		   ->setParameter("maxHeight", $maxHeight)
		   ->setFirstResult($offset)
		   ->setMaxResults(1);
		   
		return $qb->getQuery()->getOneOrNullResult();
	}
}