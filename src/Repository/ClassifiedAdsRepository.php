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

	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 't.title', 'c.publicationDate', 's.title', 'l.title', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb
		   ->join('c.language', 'l')
		   ->leftjoin('c.category', 't')
		   ->join('c.state', 's')
		   ->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$orWhere = [];
			
			foreach($aColumns as $column)
				$orWhere[] = $column." LIKE :search";

			$qb->andWhere(implode(" OR ", $orWhere))
			   ->setParameter('search', $search);
		}

		if(!empty($searchByColumns))
		{
			$aSearchColumns = ['c.id', 'c.title', 't.title', 'c.publicationDate', 's.title', 'l.title', 'c.id'];
			foreach($aSearchColumns as $i => $aSearchColumn)
			{
				if(!empty($searchByColumns[$i]) and isset($searchByColumns[$i]["value"]) and !empty($value = $searchByColumns[$i]["value"]))
				{
					$search = "%".$value."%";
					$qb->andWhere($aSearchColumn." LIKE :searchByColumn".$i)
					   ->setParameter("searchByColumn".$i, $search);
				}
			}
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
}