<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * WebDirectoryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class WebDirectoryRepository extends EntityRepository
{
	public function countAllEntities()
	{
		$qb = $this->createQueryBuilder('c');

		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $language, $count = false)
	{
		$qb = $this->createQueryBuilder('wd');

		$aColumns = array( 'wd.title', 'wd.title', 'l.abbreviation', 'wd.title');

		$qb->join('wd.language', 'l')
		    ->join('wd.state', 's')
		    ->andWhere('s.displayState = 1');

		$subQb = $this->createQueryBuilder("wd2");
		
		$subQb->select("wd2.internationalName")
		      ->join("wd2.language", "l2")
			  ->where("l2.abbreviation = :language")
			  ->andWhere("wd2.internationalName = wd.internationalName");
			  
		$qb->andWhere("l.abbreviation = :language OR wd.internationalName NOT IN (".$subQb->getDQL().")");
		
		$qb->setParameter("language", $language);

		if(!empty($sortDirColumn))
		   $qb->orderBy($aColumns[1], $sortDirColumn[0]);
		
		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$qb->andWhere('wd.title LIKE :search')
			   ->setParameter('search', $search);
		}
		if($count)
		{
			$qb->select("count(wd)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else
			$qb->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);

		return $qb->getQuery()->getResult();
	}
	
	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');

		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function countByStateAdmin($state)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		->innerjoin('c.state', 's')
		->where('s.internationalName = :state')
		->setParameter('state', $state);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 'c.link', 'c.logo', 'l.title', 's.internationalName', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->innerjoin('c.state', 's')
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
			$aSearchColumns = ['c.id', 'c.title', 'c.link', 'c.logo', 'l.title', 's.internationalName'];
			foreach($aSearchColumns as $i => $aSearchColumn)
			{
				if(!empty($searchByColumns[$i]))
				{
					$search = "%".$searchByColumns[$i]["value"]."%";
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

	public function countForDoublons($entity)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->select("count(b)")
		   ->where("b.title = :title")
		   ->setParameter("title", $entity->getTitle())
		   ->andWhere("b.link = :link")
		   ->setParameter("link", $entity->getLink())
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

	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->orderBy('c.title', 'ASC');

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";

			$qb->where("c.logo LIKE :search")
			   ->setParameter('search', $search);
		}
		if($count)
		{
			$qb->select("COUNT(DISTINCT c.logo)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else
			$qb->groupBy('c.logo')->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);

		$entities = $qb->getQuery()->getResult();
		$res = array();
		
		foreach($entities as $entity)
		{
			$photo = new \StdClass();
			$photo->photo = $entity->getLogo();
			$photo->path = $entity->getAssetImagePath();
			
			$res[] = $photo;
		}
		
		return $res;
	}
	
	public function getWebdirectoryByUrl(string $url, string $locale) {
		$domain = parse_url($url, PHP_URL_HOST);

		$qb = $this->createQueryBuilder('c');
		
		$qb->where("SUBSTRING_INDEX((SUBSTRING_INDEX(c.link, '://', -1)), '/', 1) = :domain")
		   ->setParameter("domain", $domain)
		   ->join("c.language", "l")
		   ->andWhere("l.abbreviation = :locale")
		   ->setParameter("locale", $locale);

		
		return $qb->getQuery()->getOneOrNullResult();
	}
}