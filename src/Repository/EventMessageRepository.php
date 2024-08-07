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
			->andWhere("c.yearFrom IS NOT NULL AND c.monthFrom IS NOT NULL AND c.dayFrom IS NOT NULL")
			->andWhere("CURRENT_DATE() <= CONCAT(c.yearFrom, '-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) OR CURRENT_DATE() <= CONCAT(c.yearTo, '-', LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0'))")
		    ->andWhere("c.archive = false")
			->orderBy('c.dateTo', 'asc')
			->setMaxResults(5);

		return $qb->getQuery()->getResult();
	}
	
	public function getAllEventsBetweenTwoDates($language, $startDate, $endDate)
	{
		$dayStart = str_pad($startDate->format("d"), 2, "0", STR_PAD_LEFT);
		$monthStart = str_pad($startDate->format("m"), 2, "0", STR_PAD_LEFT);

		$dayEnd = str_pad($endDate->format("d"), 2, "0", STR_PAD_LEFT);
		$monthEnd = str_pad($endDate->format("m"), 2, "0", STR_PAD_LEFT);
		
		$qb = $this->createQueryBuilder('c');
		
		$dateFromOr = null;
		$dateToOr = null;

		if($startDate->format("m") > $endDate->format("m")) {
			$dateFromOr = " OR CONCAT('0001-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) BETWEEN :monthDayStartYear AND '0001-12-31' OR
			CONCAT('0002-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) BETWEEN '0002-01-01' AND :monthDayEndYear";
			/*$dateToOr = " OR CONCAT('0001-', LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0')) BETWEEN :monthDayStartYear AND '0001-12-31' OR
			CONCAT('0002-', LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0')) BETWEEN '0002-01-01' AND :monthDayEndYear";*/

			$qb
			->setParameter('monthDayStartYear', '0001-'.$monthStart."-".$dayStart)
			->setParameter('monthDayEndYear', '0002-'.$monthEnd."-".$dayEnd);
		}

		$qb 
		->join('c.language', 'l')
		    ->join('c.state', 's')
			->where('l.abbreviation = :lang')
		    ->andWhere('s.displayState = 1')
			->setParameter('lang', $language)
			->andWhere("c.yearFrom IS NOT NULL AND c.monthFrom IS NOT NULL AND c.dayFrom IS NOT NULL")
			->andWhere("(c.yearTo IS NOT NULL AND c.monthTo IS NOT NULL AND c.dayTo IS NOT NULL) OR (c.yearTo IS NULL AND c.monthTo IS NULL AND c.dayTo IS NULL)")
			->andWhere("CONCAT(LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) BETWEEN :monthDayStart AND :monthDayEnd $dateFromOr
			            OR CONCAT(LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0')) BETWEEN :monthDayStart AND :monthDayEnd $dateToOr")
			->setParameter('monthDayStart', $monthStart."-".$dayStart)
			->setParameter('monthDayEnd', $monthEnd."-".$dayEnd)
		    ->andWhere("c.archive = false")
			;

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
			->andWhere("
			           (CONCAT(c.yearFrom, '-', :monthDay) BETWEEN CONCAT(c.yearFrom, '-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) AND CONCAT(c.yearTo, '-', LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0')) AND c.monthTo IS NOT NULL AND c.dayTo IS NOT NULL)
			        OR (c.monthTo IS NULL AND c.dayTo IS NULL AND CONCAT(c.yearFrom, '-', :monthDay) = CONCAT(c.yearFrom, '-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0'))) 
					OR (c.monthTo IS NULL AND c.dayTo IS NULL AND c.yearFrom IS NULL AND :monthDay = CONCAT(LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')))
			        OR CONCAT(c.yearTo, '-', :monthDay) BETWEEN CONCAT(c.yearFrom, '-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) AND CONCAT(c.yearTo, '-', LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0'))
			        OR c.yearFrom IS NULL AND c.yearTo IS NULL AND CONCAT('2000-', :monthDay) BETWEEN CONCAT('2000-', LPAD(c.monthFrom, 2, '0'), '-', LPAD(c.dayFrom, 2, '0')) AND CONCAT('2000-', LPAD(c.monthTo, 2, '0'), '-', LPAD(c.dayTo, 2, '0'))")
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
		$aColumns = ['c.id', 'c.title', 'l.title', 'c.id'];

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
			$aSearchColumns = ['c.id', 'c.title', 'l.abbreviation', 's.internationalName'];
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

		$aColumns = ['o.photo','o.title', 'o.publicationDate'];

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

		$aColumns = ['l.abbreviation', 'c.photo', 'c.title', 'c.publicationDate'];

		$qb->join('c.language', 'l')
		   ->leftjoin('c.state', 's')
		   ->andWhere('s.displayState = 1')
		   ->andWhere("c.archive = false");
		   
		if(!empty($theme))
		    $qb->andWhere('c.theme = :themeId')
		       ->setParameter("themeId", $theme);

		if($language == "all")
		{
			$qb->andWhere('l.abbreviation NOT IN (:currentLanguages)')
			   ->setParameter("currentLanguages", $this->currentLanguages());
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
	
	public function getAutocompleteFestival($locale, $query)
	{
		$qb = $this->createQueryBuilder("pf");
		
		$qb->where("pf.type = :festival")
		   ->setParameter("festival", \App\Entity\EventMessage::FESTIVAL_TYPE);
		
		if(!empty($locale))
		{
			$qb->leftjoin("pf.language", "la")
			   ->andWhere('la.abbreviation = :locale')
			   ->setParameter('locale', $locale);
		}

		if(!empty($query))
		{
			$query = is_array($query) ? "%".$query[0]."%" : "%".$query."%";
			$query = "%".$query."%";
			$qb->andWhere("pf.title LIKE :query")
			   ->setParameter("query", $query);
		}

		$qb->orderBy("pf.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
	}
}