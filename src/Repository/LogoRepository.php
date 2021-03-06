<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LogoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LogoRepository extends EntityRepository
{
	public function getAllLogoByLanguageAndIsActive($language, $entityId)
	{
		$qb = $this->createQueryBuilder('lo');
		
		$qb->leftjoin('lo.language', 'l')
		   ->where('lo.isActive = 1')
		   ->andWhere('l.id = :language')
		   ->setParameter('language', $language->getId())
		   ->andWhere('lo.id != :entityId')
		   ->setParameter('entityId', $entityId);
		   
		return $qb->getQuery()->getResult();
	}

	public function getOneLogoByLanguageAndIsActive($abbr)
	{
		$qb = $this->createQueryBuilder('lo');
		
		$qb->leftjoin('lo.language', 'l')
		   ->where('lo.isActive = 1')
		   ->andWhere('l.abbreviation = :abbr')
		   ->setParameter('abbr', $abbr)
		   ->setMaxResults(1);
		   
		return $qb->getQuery()->getOneOrNullResult();
	}

	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");
		
		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'lo.id', 'lo.title', 'lo.publishedAt', 'l.logo', 'lo.id');

		$qb = $this->createQueryBuilder('lo');
		$qb->join('lo.language', 'l')
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
			$qb->select("count(lo)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else
			$qb->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);

		return $qb->getQuery()->getResult();
	}
}