<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * VideoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VideoRepository extends MappedSuperclassBaseRepository
{
	public function nbrVideo($lang)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
			->join('c.language', 'o')
			->where('o.abbreviation = :lang')
			->setParameter('lang', $lang)
		    ->andWhere("c.archive = false");

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function nbrArchiveParTheme($lang, $theme)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->select("count(o)")
			->join('o.language', 'c')
			->join('o.theme', 't')
			->where('c.abbreviation = :lang')
			->setParameter('lang', $lang)
			->andWhere('t.title = :theme')
			->setParameter('theme', $theme)
		    ->andWhere("o.archive = false");

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function countEntreeVideo($theme, $lang)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
			->join('c.language', 'o')
			->join('c.theme', 't')
			->where('t.title = :theme')
			->setParameter('theme', $theme)
			->andWhere('s.displayState = 1')
			->andWhere('o.abbreviation = :lang')
			->setParameter('lang', $lang);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getTabVideo($themeId, $lang, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('o');

		$aColumns = array('o.photo','o.title', 'o.publicationDate');
		
		$qb = $this->createQueryBuilder('o');
		$qb->join('o.language', 'c')
		   ->join('o.theme', 't')
		   ->join('o.state', 's')
		   ->where('t.id = :themeId')
		   ->setParameter('themeId', $themeId)
		   ->andWhere('c.abbreviation = :lang')
		   ->setParameter('lang', $lang)
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
	
	// ADMINISTRATION
	
	public function countVideoByAvailability($state)
	{
		$qb = $this->createQueryBuilder('c');
		
		$qb->select("count(c)")
		   ->where("c.available = :state")
		   ->setParameter("state", $state);
		   
		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function countArchivedEntries()
	{
		return $this->countArchivedEntriesGeneric($this->createQueryBuilder('c'));
	}
	
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getSliderNew($language)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->join('o.state', 's')
		   ->andWhere('s.displayState = 1')
		   ->andWhere('o.available = true')
		   ->join('o.language', 'c')
		   ->andWhere('c.abbreviation = :language')
		   ->orderBy('o.publicationDate', 'DESC')
		   ->setParameter('language', $language)
		   ->andWhere("o.archive = false")
		   ->setMaxResults(1);

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 'c.platform', 's.title', 'l.title', 't.title', 'c.id');

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->join('c.theme', 't')
		   ->join('c.state', 's')
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

	public function getPreviousAndNextEntities($entity, $language)
	{
		$qb = $this->createQueryBuilder('n');
		$qb->orderBy('n.publicationDate', 'DESC')->orderBy('n.id', 'DESC')
		   ->where('n.publicationDate <= :publicationDate')
		   ->andWhere('n.id != '.$entity->getId())
		   ->setParameter('publicationDate', $entity->getPublicationDate())
		   ->join('n.language', 'l')
		   ->andWhere('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("n.archive = false")
		   ->setMaxResults(1);

		$previousEntity = $qb->getQuery()->getOneOrNullResult();

		$qb = $this->createQueryBuilder('n');
		$qb->orderBy('n.publicationDate', 'DESC')->orderBy('n.id', 'ASC')
		   ->where('n.publicationDate >= :publicationDate')->andWhere('n.id != '.$entity->getId())
		   ->setParameter('publicationDate', $entity->getPublicationDate())
		   ->join('n.language', 'l')
		   ->andWhere('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("n.archive = false")
		   ->setMaxResults(1);

		$nextEntity = $qb->getQuery()->getOneOrNullResult();

		return array("previous" => $previousEntity, "next" => $nextEntity);
	}
	
	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->orderBy('c.publicationDate', 'DESC');

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
	
	public function getSameTopics($entity)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->join('c.state', 's')
		   ->where('l.abbreviation = :lang')
		   ->setParameter('lang', $entity->getLanguage()->getAbbreviation())
		   ->andWhere('s.displayState = 1')
		   ->andWhere("c.id != :id")
		   ->setParameter("id", $entity->getId())
		   ->join('c.theme', 'o')
	       ->andWhere('o.id = :themeId')
		   ->setParameter('themeId', $entity->getTheme()->getId())
		   ->andWhere("c.archive = false")
		   ->orderBy('c.publicationDate', 'desc')
		   ->setMaxResults(3);

		return $qb->getQuery()->getResult();
	}


	public function getEntitiesPagination($page, $theme, $locale)
	{
		$qb = $this->createQueryBuilder('o');
		
		$qb->join('o.state', 's')
			->join('o.language', 'l')
			->where('l.abbreviation = :locale')
			->setParameter('locale', $locale)
			->andWhere('s.displayState = 1')
			->andWhere('o.archive = false')
			->orderBy('o.publicationDate', 'DESC');
		
		if(!empty($theme))
		{
			$qb->join('o.theme', 't')
				->andWhere('t.title = :theme')
				->setParameter('theme', $theme);
		}

		return $qb->getQuery();
	}
}