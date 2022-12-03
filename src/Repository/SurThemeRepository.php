<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * SurThemeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SurThemeRepository extends EntityRepository
{
	public function getSurTheme($lang)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->join('o.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->orderBy('o.title', 'ASC');

		return $qb->getQuery()->getResult();	
	}

	// FORM
	public function getSurThemeByLanguage($language)
	{
		$qb = $this->createQueryBuilder('s');
		$qb
		   ->join('s.language', 'l')
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->orderBy('s.title');
		return $qb;
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
		$aColumns = array( 'c.id', 'c.title', 'c.internationalName', 'l.title', 'c.id');

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
		   ->join("t.language", "lt")
		   ->orderBy("t.title", "ASC");
		   
		if(!empty($locale))
		   $qb->andWhere("lt.abbreviation = :locale")
		      ->setParameter("locale", $locale);
		   
		return array_map(function($e) { return ["id" => $e["id"], "title" => $e["title"].((isset($e["localeTitle"]) and !empty($d = $e["localeTitle"]) and $e["localeTitle"] != $e["title"]) ? " [".$d."]" : "")]; }, $qb->getQuery()->getResult());
	}
}