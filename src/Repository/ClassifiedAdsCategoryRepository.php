<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ClassifiedAdsCategoryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ClassifiedAdsCategoryRepository extends MappedSuperclassBaseRepository
{
	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');

		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	// FORM
	public function getParentClassifiedAdsCategoryByLanguage($language)
	{
		$qb = $this->createQueryBuilder('s');
		$qb
		   ->join('s.language', 'l')
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("s.parentCategory IS NULL")
		   ->orderBy('s.title');

		return $qb;
	}

	public function getClassifiedAdsCategoriesByLanguage($language)
	{
		$qb = $this->createQueryBuilder('s');
		
		if(!empty($language)) {
			$qb
			   ->join('s.language', 'l')
			   ->where('l.abbreviation = :language')
			   ->setParameter('language', $language);
		}
		
		$qb
		   ->andWhere("s.parentCategory IS NOT NULL")
		   ->orderBy('s.title');

		return $qb->getQuery()->getResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 's.title', 'l.title', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->leftjoin('c.parentCategory', 's')
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
		if($count)
		{
			$qb->select("count(c)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else
			$qb->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);

		return $qb->getQuery()->getResult();
	}

	public function countForDoublons($entity)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->select("count(b)")
		   ->where("b.title = :title")
		   ->setParameter("title", $entity->getTitle())
		   ->innerjoin("b.language", "l")
		   ->andWhere("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $entity->getLanguage()->getAbbreviation());
		   
		if($entity->getId() != null)
		{
		   $qb->andWhere("b.id != :id")
		      ->setParameter("id", $entity->getId());
		}
		
		return $qb->getQuery()->getSingleScalarResult();
	}
}