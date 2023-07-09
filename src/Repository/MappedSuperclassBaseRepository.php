<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MappedSuperclassBaseRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MappedSuperclassBaseRepository extends EntityRepository
{
	protected function currentLanguages(): Array {
		return explode(",", $_ENV["LANGUAGES"]);
	}

	public function countArchivedEntriesGeneric($qb)
	{
		$qb->select("count(c)")
		   ->where("c.archive = true");
		   
		return $qb->getQuery()->getSingleScalarResult();
	}

	public function countAllEntitiesPublicationByTheme($theme)
	{
		$qb = $this->createQueryBuilder('c');

		$qb->select('count(c)')
		   ->join('c.theme', 't')
		   ->join('c.state', 's')
		   ->where('c.archive = false')
		   ->andWhere('s.displayState = 1')
		   ->andWhere('t.id = :theme')
		   ->setParameter('theme', $theme);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function countArchived($locale): int {
		$qb = $this->createQueryBuilder('c');

		$qb->select('count(c)')
			->join('c.language', 'l')
		   ->join('c.state', 's')
			->where('l.abbreviation = :locale')
		   ->andWhere('s.displayState = 1')
		   ->andWhere('c.archive = true')
		   ->setParameter('locale', $locale);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function countArchivedByTheme($locale): Array {
		$qb = $this->createQueryBuilder('c');

		$qb->select('count(c) AS count')
		   ->addSelect('t.title')
		   ->addSelect('st.id AS parentTheme')
		   ->addSelect('t.id AS id')
		   ->join('c.language', 'l')
		   ->join('c.theme', 't')
		   ->join('c.state', 's')
		   ->join('t.parentTheme', 'st')
		   ->andWhere('s.displayState = true')
		   ->andWhere('l.abbreviation = :locale')
		   ->andWhere('c.archive = true')
		   ->setParameter('locale', $locale)
		   ->groupBy('t.title');

		return $qb->getQuery()->getResult();
	}
	
	public function getTabArchive($themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('o');

		$aColumns = array('o.title', 'o.publicationDate');

		$qb->join('o.language', 'l')
			->join('o.state', 's')
			->join('o.theme', 't')
			->where('t.id = :themeId')
			->setParameter('themeId', $themeId)
			->andWhere('s.displayState = 1')
			->andWhere('o.archive = true')
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
}