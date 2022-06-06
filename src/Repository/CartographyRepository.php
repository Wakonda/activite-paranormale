<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * gmapRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CartographyRepository extends MappedSuperclassBaseRepository
{
	public function getAllCartographyPlacesByLanguage($language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
	       ->where('l.abbreviation = :language')
	       ->setParameter('language', $language)
		   ->andWhere("c.archive = false");

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$qb->andWhere('c.title LIKE :search')
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
	
	public function nbrGMapByLang($lang)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		   ->join('c.language', 'l')
		   ->where('l.abbreviation = :lang')
		   ->setParameter('lang', $lang)
		   ->andWhere("c.archive = false");

		return $qb->getQuery()->getSingleScalarResult();
	}

	// ADMINISTRATION
	public function countArchivedEntries()
	{
		return $this->countArchivedEntriesGeneric($this->createQueryBuilder('c'));
	}
	
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');

		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 'l.title', 'c.id');

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

	public function getDatatablesForWorldIndex($language, $theme, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');

		$aColumns = array( 'l.abbreviation', 'c.photo', 'c.title', 'c.publicationDate');

		$qb->join('c.language', 'l')
		   ->leftjoin('c.state', 's')
		   ->andWhere('s.displayState = 1')
		   ->andWhere("c.archive = false");
		   
		if(!empty($theme))
		    $qb->andWhere('c.theme = :themeId')
		       ->setParameter("themeId", $theme);

		if($language == "all")
		{
			$currentLanguages = $this->currentLanguages;
			$whereIn = array();
			for($i = 0; $i < count($currentLanguages); $i++)
			{
				$whereIn[] = ':'.$currentLanguages[$i];
				$qb->setParameter(':'.$currentLanguages[$i], $currentLanguages[$i]);
			}

			$qb->andWhere('l.abbreviation NOT IN ('.implode(", ", $whereIn).')');
		}
		else
		{
			$qb->andWhere('l.abbreviation = :language');
			$qb->setParameter('language', $language);
		}
		
		if(!empty($sortDirColumn))
		   $qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);
		
		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$qb->andWhere('c.title LIKE :search')
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
	
	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->orderBy('c.publicationDate', 'DESC');

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
		$res = array();
		
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