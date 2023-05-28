<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ThemeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ThemeRepository extends MappedSuperclassBaseRepository
{
	public function nbrTheme($lang)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
			->join('c.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $lang);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getTheme($lang)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->join('o.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->orderBy('o.title');

		return $qb->getQuery()->getResult();	
	}
	
	public function getAllThemesWorld($excludeLanguages)
	{
		$qb = $this->createQueryBuilder('c');
		$qb ->join('c.language', 'l')
			->where('l.abbreviation NOT IN (:excludeLanguages)')
			->setParameter('excludeLanguages', $excludeLanguages)
			->orderBy("c.title");

		$res = [];

		foreach($qb->getQuery()->getResult() as $data)
			$res[$data->getLanguage()->getTitle()][] = ["id" => $data->getId(), "title" => $data->getTitle(), "language" => $data->getLanguage()->getAbbreviation()];
		
		return $res;
	}
	
	public function getAllThemesCurrentLanguages()
	{
		$qb = $this->createQueryBuilder('c');
		$qb ->join('c.language', 'l')
			->where('l.abbreviation IN (:currentLanguages)')
			->setParameter('currentLanguages', $this->currentLanguages())
			->orderBy("c.title");

		$res = [];

		foreach($qb->getQuery()->getResult() as $data)
			$res[$data->getLanguage()->getTitle()][] = ["id" => $data->getId(), "title" => $data->getTitle(), "language" => $data->getLanguage()->getAbbreviation()];

		return $res;
	}
	
	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');

		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	// FORM
	public function getThemeByLanguage($language)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->join('o.language', 'l')
			->where('l.abbreviation = :language')
			->setParameter('language', $language)
			->orderBy('o.title');

		return $qb;
	}

	public function getInternationalName()
	{
		$qb = $this->createQueryBuilder('th');
		$qb->groupBy('th.internationalName')
		   ->orderBy('th.internationalName');

		return $qb;
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 's.title', 'l.title', 'c.id');

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->join('c.surTheme', 's')
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
		   ->where("b.internationalName = :internationalName")
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
	
	public function getByLanguageForList($locale, $currentLocale)
	{
		$qb = $this->createQueryBuilder("t");
		
		$qbCurrentLocale = $this->createQueryBuilder("tcl");
		
		if($locale != $currentLocale)
		{
			$qbCurrentLocale->select("tcl.title")
			  ->join("tcl.language", "lcl")
			  ->where("lcl.abbreviation = :currentLocale")
			  ->andWhere("tcl.internationalName = t.internationalName");
			
			$qb->select("t.title AS title")
			   ->addSelect("(".$qbCurrentLocale->getDQL().") AS localeTitle")
		       ->setParameter("currentLocale", $currentLocale);
		} else
			$qb->select("t.title AS title");

		$qb->addSelect("t.id")
		   ->addSelect("t.internationalName")
		   ->join("t.language", "lt")
		   ->orderBy("t.title", "ASC");
		   
		if(!empty($locale))
		   $qb->andWhere("lt.abbreviation = :locale")
		      ->setParameter("locale", $locale);

		return array_map(function($e) { return ["id" => $e["id"], "internationalName" => $e["internationalName"], "title" => $e["title"].((isset($e["localeTitle"]) and !empty($d = $e["localeTitle"]) and $e["localeTitle"] != $e["title"]) ? " [".$d."]" : "")]; }, $qb->getQuery()->getResult());
	}
}