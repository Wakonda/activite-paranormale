<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * TestimonyRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TestimonyRepository extends MappedSuperclassBaseRepository
{
	public function getAllTestimonyByThemeAndLanguage($language)
	{
		$qb = $this->_em->createQueryBuilder();

		$qb->select("SUM(if( s.displayState = 1 AND o.archive = false, 1, 0)) AS total")
		   ->addSelect("t.title")
		   ->addSelect("t.id")
		   ->addSelect("pt.title AS parentTheme")
		   ->from("\App\Entity\Theme", "t")
		   ->leftjoin(\App\Entity\Testimony::class, "o", \Doctrine\ORM\Query\Expr\Join::WITH, "o.theme = t.id")
		   ->leftjoin("o.state", "s")
		   ->leftjoin('t.language', 'l')
		   ->leftjoin('t.parentTheme', 'pt')
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("t.parentTheme IS NOT NULL")
		   ->groupby("t.title")
		   ->orderBy("t.title");

		return $qb->getQuery()->getResult();
	}
	
	public function countAllTestimoniesForLeftMenu($lang)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
			->join('c.language', 'l')
			->join('c.state', 's')
			->where('s.displayState = 1')
			->andWhere('l.abbreviation = :lang')
			->setParameter('lang', $lang)
		    ->andWhere("c.archive = false");

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getTabTestimony($language, $theme)
	{	
		$qb = $this->createQueryBuilder('o');

		$qb->join('o.theme', 't')
			->where('t.title = :theme')
			->setParameter('theme', $theme)
			->join('o.language', 'l')
			->andWhere('l.abbreviation = :language')
			->setParameter('language', $language)
			->join('o.state', 's')
			->andWhere('s.displayState = 1')
		    ->andWhere("o.archive = false")
			->orderBy('o.publicationDate', 'DESC');

		return $qb->getQuery()->getResult();
	}

	// ADMINISTRATION
	public function countArchivedEntries()
	{
		return $this->countArchivedEntriesGeneric($this->createQueryBuilder('c'));
	}
	
	public function countTestimony($state)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		if($state != ""){
			$qb->innerjoin('c.state', 's')
			->where('s.internationalName = :state')
			->setParameter('state', $state);
		}
		return $qb->getQuery()->getSingleScalarResult();
	}

	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 'c.pseudoUsed', 's.title', 'c.writingDate', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.state', 's')
		   ->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		if($sortByColumn[0] == 2) {
			$qb->leftjoin("c.author", "ca")
			   ->orderBy("IF(c.pseudoUsed IS NULL, ca.username, c.pseudoUsed)", $sortDirColumn[0]);
		}

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
			$aSearchColumns = ['c.id', 'c.title', 'c.pseudoUsed', 's.internationalName', 'c.writingDate'];
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

	public function getPreviousAndNextEntities($entity, $language)
	{
		$qb = $this->createQueryBuilder('n');
		$qb->orderBy('n.publicationDate', 'DESC')->orderBy('n.id', 'DESC')
		   ->where('n.publicationDate <= :publicationDate')
		   ->andWhere('n.id != '.$entity->getId())
		   ->setParameter('publicationDate', $entity->getPublicationDate())
		   ->join('n.language', 'l')
		   ->andWhere('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("n.archive = false")
		   ->leftjoin("n.state", "s")
		   ->andWhere("s.displayState = 1")
		   ->setMaxResults(1);

		$previousEntity = $qb->getQuery()->getOneOrNullResult();

		$qb = $this->createQueryBuilder('n');
		$qb->orderBy('n.publicationDate', 'DESC')->orderBy('n.id', 'ASC')
		   ->where('n.publicationDate >= :publicationDate')->andWhere('n.id != '.$entity->getId())
		   ->setParameter('publicationDate', $entity->getPublicationDate())
		   ->join('n.language', 'l')
		   ->andWhere('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("n.archive = false")
		   ->leftjoin("n.state", "s")
		   ->andWhere("s.displayState = 1")
		   ->setMaxResults(1);

		$nextEntity = $qb->getQuery()->getOneOrNullResult();

		return ["previous" => $previousEntity, "next" => $nextEntity];
	}

	// For mobile
	public function countEntitiesByTheme($lang, $theme)
	{
		$qb = $this->createQueryBuilder('c');
	
		$qb->select("count(c)")
		   ->join('c.language', 'l')
		   ->join('c.state', 's')
		   ->where('l.abbreviation = :lang')
		   ->setParameter('lang', $lang)
		   ->andWhere('s.displayState = 1');

		if(!empty($theme))
		{
			$qb	->join('c.theme', 'o')
				->andWhere('o.title = :theme')
				->setParameter('theme', $theme);
		}
		   
		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getEntitiesPagination($page, $theme, $locale)
	{
		$qb = $this->createQueryBuilder('o');
		
		$qb->join('o.state', 's')
			->join('o.language', 'l')
			->where('l.abbreviation = :locale')
			->setParameter('locale', $locale)
			->andWhere('s.displayState = 1')
			->orderBy('o.publicationDate', 'DESC')
		    ->andWhere("o.archive = false");

		if(!empty($theme))
		{
			$qb->join('o.theme', 't')
				->andWhere('t.title = :theme')
				->setParameter('theme', $theme);
		}

		return $qb->getQuery();
	}
}