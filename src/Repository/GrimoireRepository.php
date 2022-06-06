<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * grimoireRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GrimoireRepository extends EntityRepository
{
	public function countEntree($id)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
			->join('c.surTheme', 'st')
			->join('c.language', 'l')
		    ->join('c.state', 's')
			->andWhere('st.id = :id')
			->andWhere('c.archive = false')
			->setParameter('id', $id)
		    ->andWhere('s.displayState = 1');

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getTabGrimoire($themeId, $lang, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('o');

		$aColumns = array('o.photo','o.title');

		$qb->join('o.language', 'l')
			->join('o.surTheme', 'st')
		    ->join('o.state', 's')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->andWhere('st.id = :themeId')
			->setParameter('themeId', $themeId)
			->andWhere('o.archive = false')
		    ->andWhere('s.displayState = 1');

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
	
	public function findByDisplayState($id)
	{
		$qb = $this->createQueryBuilder("n");
		$qb->leftjoin("n.state", "s")
		   ->where("s.displayState = 1")
		   ->andWhere("n.id = :id")
		   ->setParameter("id", $id);
		
		return $qb->getQuery()->getOneOrNullResult();
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
		$aColumns = array( 'c.id', 'c.title', 'st.title', 's.title', 'l.title', 'c.id');

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->join('c.surTheme', 'st')
		   ->join('c.state', 's')
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

	public function countByStateAdmin($state)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		->innerjoin('c.state', 's')
		->where('s.internationalName = :state')
		->setParameter('state', $state);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getByStateAdmin($state)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->innerjoin('o.state', 's')
		->where('s.internationalName = :state')
		->setParameter('state', $state);

		return $qb->getQuery()->getResult();
	}

	public function getPreviousAndNextEntities($entity, $language)
	{
		$qb = $this->createQueryBuilder('n');
		$qb->orderBy('n.writingDate', 'DESC')->orderBy('n.id', 'DESC')
		   ->where('n.writingDate <= :writingDate')
		   ->andWhere('n.id != '.$entity->getId())
		   ->setParameter('writingDate', $entity->getWritingDate())
		   ->join('n.language', 'l')
		   ->andWhere('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->leftjoin("n.state", "s")
		   ->andWhere("s.displayState = 1")
		   ->andWhere('n.archive = false')
		   ->setMaxResults(1);

		$previousEntity = $qb->getQuery()->getOneOrNullResult();

		$qb = $this->createQueryBuilder('n');
		$qb->orderBy('n.writingDate', 'DESC')->orderBy('n.id', 'ASC')
		   ->where('n.writingDate >= :writingDate')->andWhere('n.id != '.$entity->getId())
		   ->setParameter('writingDate', $entity->getWritingDate())
		   ->join('n.language', 'l')
		   ->andWhere('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->leftjoin("n.state", "s")
		   ->andWhere("s.displayState = 1")
		   ->andWhere('n.archive = false')
		   ->setMaxResults(1);

		$nextEntity = $qb->getQuery()->getOneOrNullResult();

		return array("previous" => $previousEntity, "next" => $nextEntity);
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

	// For mobile
	public function countEntitiesByTheme($lang, $theme)
	{
		$qb = $this->createQueryBuilder('c');
	
		$qb->select("count(c)")
		   ->join('c.language', 'l')
		   ->join('c.state', 's')
		   ->where('l.abbreviation = :lang')
		   ->setParameter('lang', $lang)
		   ->andWhere('c.archive = false')
		   ->andWhere('s.displayState = 1');
		   
		if(!empty($theme))
		{
			$qb	->join('c.surTheme', 'o')
				->andWhere('o.title = :theme')
				->setParameter('theme', $theme);
		}

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getEntitiesPagination($page, $theme, $locale)
	{	
		$qb = $this->createQueryBuilder('o');
		
		$qb->join('o.language', 'l')
		   ->join('o.state', 's')
		   ->where('l.abbreviation = :locale')
		   ->andWhere('o.archive = false')
		   ->setParameter('locale', $locale)
		   ->andWhere('s.displayState = 1')
		   ->orderBy('o.writingDate', 'DESC');

		if(!empty($theme))
		{
			$qb->join('o.surTheme', 't')
			   ->andWhere('t.title = :theme')
			   ->setParameter('theme', $theme);
		}

		return $qb->getQuery();
	}

	public function getRandom($locale)
	{
		$qb = $this->createQueryBuilder("o");

		$qb->select("COUNT(o) AS countRow")
		   ->join('o.language', 'l')
		   ->where('l.abbreviation = :locale')
		   ->andWhere('o.archive = false')
		   ->setParameter('locale', $locale);
		
		$max = max($qb->getQuery()->getSingleScalarResult() - 1, 0);
		$offset = rand(0, $max);

		$qb = $this->createQueryBuilder("o");

		$qb->join('o.language', 'l')
		   ->where('l.abbreviation = :locale')
		   ->andWhere('o.archive = false')
		   ->setParameter('locale', $locale)
		   ->setFirstResult($offset)
		   ->setMaxResults(1);

		return $qb->getQuery()->getOneOrNullResult();
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
		   ->addSelect('st.id AS surTheme')
		   ->addSelect('t.id AS id')
			->join('c.language', 'l')
		   ->join('c.surTheme', 't')
		   ->join('c.state', 's')
		   ->join('t.menuGrimoire', 'st')
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
			->join('o.surTheme', 't')
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