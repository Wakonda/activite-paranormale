<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * EventMessageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventMessageRepository extends MappedSuperclassBaseRepository
{
	public function getLastEventsToDisplayIndex($language)
	{
		$qb = $this->createQueryBuilder('c');

		$qb ->join('c.language', 'l')
		    ->join('c.state', 's')
			->where('l.abbreviation = :lang')
		    ->andWhere('s.displayState = 1')
			->setParameter('lang', $language)
			->andWhere('CURRENT_DATE() <= c.dateFrom OR CURRENT_DATE() <= c.dateTo')
		    ->andWhere("c.archive = false")
			->orderBy('c.dateTo', 'asc')
			->setMaxResults(5);

		return $qb->getQuery()->getResult();
	}
	
	public function getAllEventsBetweenTwoDates($language, $startDate, $endDate)
	{
		$qb = $this->createQueryBuilder('c');

		$qb ->join('c.language', 'l')
		    ->join('c.state', 's')
			->where('l.abbreviation = :lang')
		    ->andWhere('s.displayState = 1')
			->setParameter('lang', $language)
			->andWhere('c.dateFrom BETWEEN :startDate AND :endDate')
			->andWhere('c.dateTo BETWEEN :startDate AND :endDate')
			->setParameter('startDate', $startDate)
			->setParameter('endDate', $endDate)
		    ->andWhere("c.archive = false");

		return $qb->getQuery()->getResult();
	}
	
	public function getAllEventsByDayAndMonth($day, $month, $language)
	{
		$day = str_pad($day, 2, "0", STR_PAD_LEFT);
		$month = str_pad($month, 2, "0", STR_PAD_LEFT);

		$qb = $this->createQueryBuilder('c');

		$qb ->join('c.language', 'l')
		    ->join('c.state', 's')
			->where('l.abbreviation = :lang')
		    ->andWhere('s.displayState = 1')
			->setParameter('lang', $language)
			->andWhere("(CONCAT(c.yearFrom, '-', :monthDay) BETWEEN CONCAT(c.yearFrom, '-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) AND CONCAT(c.yearTo, '-', LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0')) AND c.monthTo IS NOT NULL AND c.dayTo IS NOT NULL) OR (c.monthTo IS NULL AND c.dayTo IS NULL AND CONCAT(c.yearFrom, '-', :monthDay) = CONCAT(c.yearFrom, '-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')))")
			->orWhere("CONCAT(c.yearTo, '-', :monthDay) BETWEEN CONCAT(c.yearFrom, '-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) AND CONCAT(c.yearTo, '-', LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0'))")
			->setParameter('monthDay', $month."-".$day)
		    ->andWhere("c.archive = false")
			->orderBy("c.yearFrom", "DESC")
			->addOrderBy("c.yearTo", "DESC");

		return $qb->getQuery()->getResult();
	}
	
	public function getAllEventsByMonthOrYear($year, $month, $language)
	{
		$qb = $this->createQueryBuilder('c');

		$qb ->join('c.language', 'l')
		    ->join('c.state', 's')
			->where('l.abbreviation = :lang')
		    ->andWhere('s.displayState = 1')
			->setParameter('lang', $language)
			->setParameter('year', $year)
		    ->andWhere("c.archive = false");
			
		if(!empty($month)) {
			$month = str_pad($month, 2, "0", STR_PAD_LEFT);
		
			$qb->andWhere("(c.yearFrom = :year AND c.monthFrom = :month) OR (c.yearTo = :year AND c.monthTo = :month)")
			   ->setParameter("month", $month)
			   ->orderBy("c.monthFrom", "DESC")
			   ->addOrderBy("c.monthTo", "DESC");
		} else {
			$qb->andWhere("c.yearFrom = :year OR c.yearTo = :year");
		}
		
		$qb ->addOrderBy("c.yearFrom", "DESC")
			->addOrderBy("c.yearTo", "DESC");

		return $qb->getQuery()->getResult();
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

	public function getByStateAdmin($state)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->innerjoin('o.state', 's')
		->where('s.internationalName = :state')
		->setParameter('state', $state);

		return $qb->getQuery()->getResult();
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

	public function getTab($themeId, $lang, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{	
		$qb = $this->createQueryBuilder('o');

		$aColumns = array('o.photo','o.title', 'o.publicationDate');

		$qb->join('o.language', 'c')
		   ->join('o.theme', 't')
		   ->where('t.id = :themeId')
		   ->setParameter('themeId', $themeId)
		   ->andWhere('c.abbreviation = :lang')
		   ->setParameter('lang', $lang)
		   ->join('o.state', 's')
		   ->andWhere('s.displayState = 1')
		   ->andWhere("o.archive = false")
		   ->orderBy('o.publicationDate');

		if(!empty($sortDirColumn))
		   $qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);
		
		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$qb->andWhere('o.title LIKE :search')
			   ->setParameter('search', $search);
		}
		if($count)
		{
			$qb->select("count(o)");
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
}