<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

use App\Entity\UsefulLink;

/**
 * UsefulLinkRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UsefulLinkRepository extends EntityRepository
{
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $filter = null, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 'c.category', 'c.links', 'c.id'];

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

		if(!empty($filter)) {
			if(isset($filter["category_filter"]) and !empty($value = $filter["category_filter"])) {
				$qb->andWhere("c.category = :valueCategory")
				   ->setParameter("valueCategory", $value);
			} else {
			$qb->andWhere("c.category != :valueCategory")
			   ->setParameter("valueCategory", UsefulLink::RESOURCE_FAMILY);
		}

			if(isset($filter["tags_filter"]) and !empty($value = $filter["tags_filter"])) {
				$qb->andWhere("JSON_CONTAINS(JSON_EXTRACT(LOWER(c.tags), '$[*].value'), LOWER('\"".$value."\"'), '$') = true");
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

	public function getDevelopmentLinksForIndex($locale, $tag = null)
	{
		$qb = $this->createQueryBuilder('c');

		$qb->join('c.language', 'l')
			->where('l.abbreviation = :locale')
			->setParameter('locale', $locale)
			->andWhere("c.category = :category")
			->setParameter("category", UsefulLink::DEVELOPMENT_FAMILY);


		if(!empty($tag)) {
			$qb->join("c.usefullinkTags", "t")
			   ->andWhere("t.title = :tag")
			   ->setParameter("tag", $tag);
		}

		$qb->orderBy("c.id", "desc");
		
		return $qb->getQuery();
	}
	
	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		return $this->getFileSelectorColorboxIllustrationAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count);
	}

	public function counterByTags() {
		$qb = $this->createQueryBuilder('u');
		
		$qb->select("ut.title AS text, count(u) AS weight")
		   ->join("u.usefullinkTags", "ut")
		   ->groupBy("ut.title");
		   
		return $qb->getQuery()->getResult();
	}
}