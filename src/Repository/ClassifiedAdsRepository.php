<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ClassifiedAdsRepository
 */
class ClassifiedAdsRepository extends EntityRepository
{
	public function getClassifiedAds($datas, $locale)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->innerjoin("b.language", "l")
		   ->join("b.state", "s")
		   ->where("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $locale)
		   ->andWhere("b.archive = false")
		   ->andWhere("s.displayState = true");

		if(isset($datas["keywords"])) {
			$qb->andWhere("(b.title LIKE :keyword OR b.text LIKE :keyword OR b.location LIKE :keyword)")
			   ->setParameter("keyword", "%".$datas["keywords"]."%");
		}

		if(isset($datas["region"]) and !empty($region = $datas["region"])) {
			$qb->andWhere("b.location LIKE :region")
			   ->setParameter("region", "%".$region."%");
		} else {
			if(isset($datas["location_raw"]) and !empty($region = $datas["location_raw"])) {
				$qb->andWhere("b.location LIKE :region")
				   ->setParameter("region", "%".$region."%");
			}
		}

		if(isset($datas["country"]) and !empty($country = $datas["country"])) {
			$qb->andWhere("JSON_EXTRACT(b.location, '$.country_code') = :country")
			   ->setParameter("country", $country->getInternationalName());
		}

		if(isset($datas["category"]) and !empty($category = $datas["category"])) {//dd("zzzz");
			$qb->andWhere("b.category = :category")
			   ->setParameter("category", $category);
		}

		$qb->orderBy('b.writingDate', 'DESC');

		return $qb->getQuery();
	}

	public function countByLanguage($language)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		   ->join('c.language', 'l')
		   ->join("c.state", "s")
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("c.archive = false")
		   ->andWhere("s.displayState = true");

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
		$aColumns = ['c.id', 'c.title', 't.title', 'c.publicationDate', 's.title', 'l.title', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb
		   ->leftjoin('c.language', 'l')
		   ->leftjoin('c.category', 't')
		   ->leftjoin('c.state', 's')
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
			$aSearchColumns = ['c.id', 'c.title', 't.title', 'c.publicationDate', 's.internationalName', 'l.title', 'c.id'];
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

	public function countByStateAdmin($state)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		->innerjoin('c.state', 's')
		->where('s.internationalName = :state')
		->setParameter('state', $state);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function countByCategory($language)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("paca.title, ca.title, count(c) as count")
		   ->join('c.language', 'l')
		   ->join("c.state", "s")
		   ->join("c.category", "ca")
		   ->join("ca.parentCategory", "paca")
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("c.archive = false")
		   ->andWhere("s.displayState = true")
		   ->groupBy("ca.title");

		$res = [];
		$counters = $qb->getQuery()->getResult();
		$categories = $this->getEntityManager()->createQuery('SELECT a.title, pc.title AS parentCategory FROM App\Entity\ClassifiedAdsCategory a JOIN a.parentCategory pc JOIN a.language l WHERE l.abbreviation = :language ORDER BY a.title ASC')->setParameter("language", $language)->getResult();

		foreach($categories as $category) {
			$key = array_search($category["title"], array_column($counters, "title"));
			$res[$category["parentCategory"]][] = ["title" => $category["title"], "count" => $key !== false ? $counters[$key]["count"] : 0];
		}

		return $res;
	}
}