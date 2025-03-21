<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

use App\Entity\Biography;
use App\Entity\EntityLinkBiography;

/**
 * BiographyRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BiographyRepository extends MappedSuperclassBaseRepository
{
	public function countBiography($locale)
	{
		$qb = $this->createQueryBuilder('c');

		$qb->select("count(c)")
			->join('c.language', 'l')
			->where('l.abbreviation = :locale')
			->setParameter('locale', $locale);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getBiographies($locale)
	{
		$qb = $this->createQueryBuilder('c');

		$qb	->join('c.language', 'l')
			->where('l.abbreviation = :locale')
			->setParameter('locale', $locale);

		return $qb->getQuery()->getResult();
	}
	
	public function getDatatablesForIndex($locale, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $datas = [], $count = false)
	{
		$qb = $this->createQueryBuilder('c');

		$aColumns = ['c.title', 'c.title'];

		$qb->join('c.language', 'l')
		   ->where('l.abbreviation = :locale')
		   ->setParameter('locale', $locale);

		if(isset($datas["title"]) and !empty($title = $datas["title"])) {
			$search = "%".$title."%";
			$qb->andWhere('c.title LIKE :search')
			   ->setParameter('search', $search);
		}

		if(isset($datas["occupation"]) and !empty($occupation = $datas["occupation"])) {
			$qb->join(EntityLinkBiography::class, "elb", \Doctrine\ORM\Query\Expr\Join::WITH, "elb.biography = c.id")
			   ->andWhere('elb.occupation = :occupation')
			   ->setParameter('occupation', $occupation);
		}
		if(isset($datas["country"]) and !empty($country = $datas["country"])) {
			$qb->join("c.nationality", "co")
			   ->andWhere('co.internationalName = :abbreviation')
			   ->setParameter('abbreviation', $country->getInternationalName());
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

	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $toComplete = null, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 'l.title', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
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

		if(!empty($toComplete)) {
			switch($toComplete) {
				case "textToComplete":
					$qb->andWhere("c.text IS NULL");
					break;
				case "imageToComplete":
					$qb->andWhere("c.illustration IS NULL");
					break;
				case "birthDateToComplete":
					$qb->andWhere("c.birthDate IS NULL");
					break;
				case "deathDateToComplete":
					$qb->andWhere("c.deathDate IS NULL");
					break;
				case "nationalityToComplete":
					$qb->andWhere("c.nationality IS NULL");
					break;
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

	public function getBiographyInCorrectLanguage($entity, $locale)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->leftjoin("b.language", "l")
		   ->where("l.abbreviation = :locale")
		   ->setParameter("locale", $locale)
		   ->andWhere("b.internationalName = :internationalName")
		   ->setParameter("internationalName", $entity->getInternationalName());

		return $qb->getQuery()->getOneOrNullResult();
	}
	
	public function getAllEventsByDayAndMonth($day, $month, $language)
	{
		$day = str_pad($day, 2, "0", STR_PAD_LEFT);
		$month = str_pad($month, 2, "0", STR_PAD_LEFT);

		$qb = $this->createQueryBuilder('c');

		$qb ->join('c.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $language)
			->andWhere("(REGEXP(c.birthDate, :regexp) = true AND CONCAT(LPAD(EXTRACT(MONTH FROM TRIM(LEADING '-' FROM c.birthDate)), 2, '0'), '-', LPAD(EXTRACT(DAY FROM TRIM(LEADING '-' FROM c.birthDate)), 2, '0')) = :monthDay
			            OR REGEXP(c.deathDate, :regexp) = true AND CONCAT(LPAD(EXTRACT(MONTH FROM TRIM(LEADING '-' FROM c.deathDate)), 2, '0'), '-', LPAD(EXTRACT(DAY FROM TRIM(LEADING '-' FROM c.deathDate)), 2, '0')) = :monthDay)")
			->setParameter("regexp", "^(-[0-9]{3,4}|[0-9]{3,4})-[0-9]{2}-[0-9]{2}$")
			->setParameter('monthDay', $month."-".$day)
			->orderBy("EXTRACT(YEAR FROM c.birthDate)", "DESC")
			->addOrderBy("EXTRACT(YEAR FROM c.deathDate)", "DESC");

		return $qb->getQuery()->getResult();
	}
	
	public function getAllEventsByDayAndMonthBetween($startDate, $endDate, $language)
	{
		$dayStart = str_pad($startDate->format("d"), 2, "0", STR_PAD_LEFT);
		$monthStart = str_pad($startDate->format("m"), 2, "0", STR_PAD_LEFT);

		$dayEnd = str_pad($endDate->format("d"), 2, "0", STR_PAD_LEFT);
		$monthEnd = str_pad($endDate->format("m"), 2, "0", STR_PAD_LEFT);

		$birthDateOr = "";
		$deathDateOr = "";

		$qb = $this->createQueryBuilder('c');

		if($startDate->format("m") > $endDate->format("m")) {
			$birthDateOr = " OR CONCAT('0001-', LPAD(EXTRACT(MONTH FROM c.birthDate), 2, '0'), '-', LPAD(EXTRACT(DAY FROM c.birthDate), 2, '0')) BETWEEN :monthDayStartYear AND '0001-12-31' OR
			CONCAT('0002-', LPAD(EXTRACT(MONTH FROM c.birthDate), 2, '0'), '-', LPAD(EXTRACT(DAY FROM c.birthDate), 2, '0')) BETWEEN '0002-01-01' AND :monthDayEndYear";
			$deathDateOr = " OR CONCAT('0001-', LPAD(EXTRACT(MONTH FROM c.deathDate), 2, '0'), '-', LPAD(EXTRACT(DAY FROM c.deathDate), 2, '0')) BETWEEN :monthDayStartYear AND '0001-12-31' OR
			CONCAT('0002-', LPAD(EXTRACT(MONTH FROM c.deathDate), 2, '0'), '-', LPAD(EXTRACT(DAY FROM c.deathDate), 2, '0')) BETWEEN '0002-01-01' AND :monthDayEndYear";

			$qb
			->setParameter('monthDayStartYear', '0001-'.$monthStart."-".$dayStart)
			->setParameter('monthDayEndYear', '0002-'.$monthEnd."-".$dayEnd);
		}

		$qb ->join('c.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $language)
			->andWhere("(REGEXP(c.birthDate, :regexp) = true AND (CONCAT(LPAD(EXTRACT(MONTH FROM c.birthDate), 2, '0'), '-', LPAD(EXTRACT(DAY FROM c.birthDate), 2, '0')) BETWEEN :monthDayStart AND :monthDayEnd $birthDateOr)
			            OR REGEXP(c.deathDate, :regexp) = true AND CONCAT(LPAD(EXTRACT(MONTH FROM c.deathDate), 2, '0'), '-', LPAD(EXTRACT(DAY FROM c.deathDate), 2, '0')) BETWEEN :monthDayStart AND :monthDayEnd $deathDateOr)")
			->setParameter("regexp", "^[0-9]{4}-[0-9]{2}-[0-9]{2}$")
			->setParameter('monthDayStart', $monthStart."-".$dayStart)
			->setParameter('monthDayEnd', $monthEnd."-".$dayEnd)
			->orderBy("EXTRACT(YEAR FROM c.birthDate)", "DESC")
			->addOrderBy("EXTRACT(YEAR FROM c.deathDate)", "DESC");

		return $qb->getQuery()->getResult();
	}
	
	public function getAllEventsByFeastDay($feastDay, $language)
	{
		$qb = $this->createQueryBuilder('c');

		$qb ->join('c.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $language)
			->andWhere("c.feastDay = :feastDay")
			->setParameter("feastDay", $feastDay);

		return $qb->getQuery()->getResult();
	}



	public function getAllEventsByMonthOrYear($year, $month, $language)
	{
		$qb = $this->createQueryBuilder('c');

		$qb ->join('c.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $language);

		if(!empty($month)) {
			$month = str_pad($month, 2, "0", STR_PAD_LEFT);
			$qb->andWhere("((REGEXP(c.birthDate, :regexYearMonth) = true AND EXTRACT(YEAR FROM CONCAT(c.birthDate, '-01')) = :year AND EXTRACT(MONTH FROM CONCAT(c.birthDate, '-01')) = :month) OR (REGEXP(c.birthDate, :regexYearMonthDay) = true AND EXTRACT(YEAR FROM c.birthDate) = :year AND EXTRACT(MONTH FROM c.birthDate) = :month)
			   OR (REGEXP(c.deathDate, :regexYearMonth) = true AND EXTRACT(YEAR FROM CONCAT(c.deathDate, '-01')) = :year AND EXTRACT(MONTH FROM CONCAT(c.deathDate, '-01')) = :month) OR (REGEXP(c.deathDate, :regexYearMonthDay) = true AND EXTRACT(YEAR FROM c.deathDate) = :year AND EXTRACT(MONTH FROM c.deathDate) = :month))")
			   ->setParameter("month", $month);
		} else {
			$qb->andWhere("((REGEXP(c.birthDate, :regexYearMonth) = true AND EXTRACT(YEAR FROM CONCAT(c.birthDate, '-01')) = :year) OR (REGEXP(c.birthDate, :regexYearMonthDay) = true AND EXTRACT(YEAR FROM c.birthDate) = :year) OR (REGEXP(c.birthDate, :regexYear) = true AND c.birthDate = :year)
			   OR (REGEXP(c.deathDate, :regexYearMonth) = true AND EXTRACT(YEAR FROM CONCAT(c.deathDate, '-01')) = :year) OR (REGEXP(c.deathDate, :regexYearMonthDay) = true AND EXTRACT(YEAR FROM c.deathDate) = :year) OR (REGEXP(c.deathDate, :regexYear) = true AND c.deathDate = :year))")
			   ->setParameter("regexYear", '^[0-9]{4}$')
			   ->orderBy("IF(REGEXP(c.birthDate, :regexYearMonthDay) = true, EXTRACT(YEAR FROM c.birthDate), IF(REGEXP(c.birthDate, :regexYearMonth) = true, EXTRACT(YEAR FROM CONCAT(c.birthDate, '-01')), c.birthDate))", "DESC")
			   ->addOrderBy("IF(REGEXP(c.deathDate, :regexYearMonthDay) = true, EXTRACT(YEAR FROM c.deathDate), IF(REGEXP(c.deathDate, :regexYearMonth) = true, EXTRACT(YEAR FROM CONCAT(c.deathDate, '-01')), c.deathDate))", "DESC");
		}

		$qb->setParameter("regexYearMonth", '^[0-9]{4}-[0-9]{2}$')
		   ->setParameter("regexYearMonthDay", '^[0-9]{4}-[0-9]{2}-[0-9]{2}$')
		   ->setParameter("year", $year);

		return $qb->getQuery()->getResult();
	}

	// FORM
	public function getBiographyByLanguage($language)
	{
		$qb = $this->createQueryBuilder('li');
		$qb->join('li.language', 'l')
		   ->where('l.abbreviation = :lang')
		   ->setParameter('lang', $language)
		   ->orderBy('li.title');

		return $qb;
	}

	public function getBiographiesByLanguagesAndInternationalName($languages, $internationalName)
	{
		$qb = $this->createQueryBuilder('bi');

		$qb->select('l.abbreviation AS abbr, bi.title AS title, l.logo AS logo, bi.id AS id')
		   ->leftjoin('bi.language', 'l')
		   ->where('bi.internationalName = :internationalName')
		   ->setParameter('internationalName', $internationalName);

		return $qb->getQuery()->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
	}
	
	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		return $this->getFileSelectorColorboxIllustrationAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count);
	}

	public function getDatatablesForWorldIndex($language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');

		$aColumns = ['l.abbreviation', 'il.titleFile', 'c.title'];

		$qb->join('c.language', 'l')
		   ->leftjoin("c.illustration", "il");

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
	
	public function getAutocomplete($locale, $query, $kinds = [])
	{
		$qb = $this->createQueryBuilder("b");
		
		$qb->select("MAX(b.title) AS title")
		   ->addSelect("MAX(b.birthDate) AS birthDate")
		   ->addSelect("b.id AS id")
		   ->addSelect("MAX(b.deathDate) AS deathDate")
		   ->addSelect("GROUP_CONCAT(l.title SEPARATOR '#') AS languages")
		   ->addSelect("b.internationalName")
		   ->addSelect("b.wikidata")
		   ->join("b.language", "l")
		   ->groupBy("b.internationalName")
		   ->addGroupBy("b.wikidata");
		
		if(!empty($locale))
		{
			$qb
			   ->leftjoin("b.language", "la")
			   ->where('la.abbreviation = :locale')
			   ->setParameter('locale', $locale);
		}

		if(!empty($query))
		{
			$query = is_array($query) ? "%".$query[0]."%" : "%".$query."%";
			$query = "%".$query."%";
			$qb->andWhere("b.title LIKE :query")
			   ->setParameter("query", $query);
		}

		if(!empty($kinds)) {
			$qb->andWhere("b.kind IN (:kinds)")
			   ->setParameter("kinds", $kinds);
		} else {
			$qb->andWhere("b.kind = :person")
			   ->setParameter("person", Biography::PERSON);
		}

		$qb->orderBy("b.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
	}
	
	public function getBiographyByWikidataOrTitle(?string $title = null, ?string $wikidata = null)
	{
		if(empty($title) and empty($wikidata))
			return [];

		$qb = $this->createQueryBuilder("b");

		$qb->select("MAX(b.title) AS title")
		   ->addSelect("MAX(b.birthDate) AS birthDate")
		   ->addSelect("MAX(b.deathDate) AS deathDate")
		   ->addSelect("GROUP_CONCAT(l.title SEPARATOR '#') AS languages")
		   ->addSelect("GROUP_CONCAT(l.id SEPARATOR '#') AS languages_id")
		   ->addSelect("i.realNameFile AS illustration")
		   ->addSelect("b.internationalName")
		   ->addSelect("b.wikidata")
		   ->addSelect("n.title AS nationality")
		   ->join("b.language", "l")
		   ->leftjoin("b.illustration", "i")
		   ->leftjoin("b.nationality", "n")
		   ->groupBy("b.internationalName");

		if(!empty($title))
			$qb->where("b.title = :title")
		       ->setParameter("title", $title);

		if(!empty($wikidata))
			$qb->orWhere("b.wikidata = :wikidata")
		       ->setParameter("wikidata", $wikidata)
		       ->addGroupBy("b.wikidata");

		return $qb->getQuery()->getResult();
	}
}