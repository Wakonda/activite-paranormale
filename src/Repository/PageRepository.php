<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PageRepository extends EntityRepository
{
	public function getPageByLanguageAndType($language, $type)
	{
		$qb = $this->createQueryBuilder('p');

		$qb
			->join('p.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $language)
			->andWhere('p.internationalName = :type')
			->setParameter('type', $type);
		
		return $qb->getQuery()->getOneOrNullResult();
	}

	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()>getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 'c.internationalName', 'l.title', 'c.id');

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			foreach($aColumns as $column)
			{
				$qb->orWhere($column." LIKE :search")
				   ->setParameter('search', $search);
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