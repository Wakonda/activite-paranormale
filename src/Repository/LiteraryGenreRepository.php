<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LiteraryGenreRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LiteraryGenreRepository extends MappedSuperclassBaseRepository
{
	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 'c.id'];

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

	public function countForDoublons($entity)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->select("count(b)")
		   ->where("b.title = :title")
		   ->setParameter("title", $entity->getTitle())
		   ->andWhere("b.internationalName = :internationalName")
		   ->setParameter("internationalName", $entity->getInternationalName())
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

	public function getGenreByLanguage($language)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->join('o.language', 'l')
			->where('l.abbreviation = :language')
			->setParameter('language', $language)
			->orderBy('o.title');

		return $qb;
	}

	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->orderBy('c.title', 'ASC');

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";

			$qb->where("c.photo LIKE :search")
			   ->setParameter('search', $search);
		}
		if($count)
		{
			$qb->select("COUNT(DISTINCT c.photo)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else
			$qb->groupBy('c.photo')->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);

		$entities = $qb->getQuery()->getResult();
		$res = [];
		
		foreach($entities as $entity)
		{
			$photo = new \StdClass();
			$photo->photo = $entity->getPhoto();
			$photo->path = $entity->getAssetImagePath();
			
			$res[] = $photo;
		}
		
		return $res;
	}
}