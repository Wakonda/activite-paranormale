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
		$aColumns = array( 'c.id', 'c.title', 'c.category', 'c.links', 'c.id');

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
// dd($filter);
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
			$qb->andWhere("JSON_CONTAINS(JSON_EXTRACT(LOWER(c.tags), '$[*].value'), LOWER('\"".$tag."\"'), '$') = true");
		}

		$qb->orderBy("c.id", "desc");
		
		return $qb->getQuery();
	}
	
	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->orderBy('c.internationalName', 'DESC')
		   ->join("c.illustration", "il")
		   ->where("il.realNameFile IS NOT NULL");

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";

			$qb->andWhere("il.titleFile LIKE :search")
			   ->setParameter('search', $search);
		}
		if($count)
		{
			$qb->select("COUNT(DISTINCT il.realNameFile)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else
			$qb->groupBy('il.realNameFile')->orderBy("c.id", "DESC")->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);

		$entities = $qb->getQuery()->getResult();
		$res = array();
		
		foreach($entities as $entity)
		{
			$photo = new \StdClass();
			$photo->photo = $entity->getIllustration()->getTitleFile();
			$photo->path = $entity->getAssetImagePath();
			
			$res[] = $photo;
		}
		
		return $res;
	}
}
