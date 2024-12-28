<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PhotoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PhotoRepository extends MappedSuperclassBaseRepository
{
	public function nbrPicture($lang)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
			->join('c.language', 'o')
			->where('o.abbreviation = :lang')
			->join('c.state', 's')
			->andWhere('s.displayState = 1')
			->setParameter('lang', $lang)
			->andWhere("c.archive = false");

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getAllPhotoByThemeAndLanguage($language)
	{
		$qb = $this->_em->createQueryBuilder();

		$qb->select("SUM(if( s.displayState = 1 AND o.archive = false, 1, 0)) AS total")
		   ->addSelect("t.title")
		   ->addSelect("t.id")
		   ->addSelect("pt.title AS parentTheme")
		   ->from("\App\Entity\Theme", "t")
		   ->leftjoin(\App\Entity\Photo::class, "o", \Doctrine\ORM\Query\Expr\Join::WITH, "o.theme = t.id")
		   ->leftjoin("o.state", "s")
		   ->leftjoin('t.language', 'l')
		   ->leftjoin('t.parentTheme', 'pt')
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("t.parentTheme IS NOT NULL")
		   ->groupby("t.title")
		   ->orderBy("t.title");

		return $qb->getQuery()->getResult();
	}

	public function getTabPicture($themeId, $lang, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('o');

		$aColumns = ['il.titleFile','o.title', 'o.publicationDate'];

		$qb->join('o.language', 'c')
		   ->join('o.theme', 't')
		   ->join("o.illustration", "il")
		   ->where('t.id = :themeId')
		   ->setParameter('themeId', $themeId)
		   ->andWhere('c.abbreviation = :lang')
		   ->setParameter('lang', $lang)
		   ->join('o.state', 's')
		   ->andWhere('s.displayState = 1')
		   ->orderBy('o.publicationDate')
		   ->andWhere("o.archive = false");

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

	public function getSliderNew($language)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->join('o.state', 's')
		   ->andWhere('s.displayState = 1')
		   ->join('o.language', 'c')
		   ->andWhere('c.abbreviation = :language')
		   ->orderBy('o.publicationDate', 'DESC')
		   ->setParameter('language', $language)
		   ->setMaxResults(5)
		   ->andWhere("o.archive = false");

		return $qb->getQuery()->getResult();
	}

	// ADMINISTRATION
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

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 'c.publicationDate', 's.internationalName', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb
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
		if(!empty($searchByColumns))
		{
			foreach($aColumns as $i => $aSearchColumn)
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
	
	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		return $this->getFileSelectorColorboxIllustrationAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count);
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
		   ->andWhere("n.archive = false")
		   ->setParameter('language', $language)
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

		return ["previous" => $previousEntity, "next" => $nextEntity];
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
		   ->andWhere('s.displayState = 1');

		if(!empty($theme))
		{
			$qb	->join('c.theme', 'o')
				->andWhere('o.title = :theme')
				->setParameter('theme', $theme);
		}

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getEntitiesPagination($page, $theme, $locale)
	{
		$qb = $this->createQueryBuilder('o');

		$qb->join('o.state', 's')
			->join('o.language', 'l')
			->where('l.abbreviation = :locale')
			->setParameter('locale', $locale)
			->andWhere('s.displayState = 1')
		    ->andWhere("o.archive = false")
			->orderBy('o.publicationDate', 'DESC');

		if(!empty($theme))
		{
			$qb->join('o.theme', 't')
				->andWhere('t.title = :theme')
				->setParameter('theme', $theme);
		}

		return $qb->getQuery();
	}

	public function getDatatablesForWorldIndex($language, $theme, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');

		$aColumns = ['l.abbreviation', 'il.titleFile', 'c.title', 'c.publicationDate'];

		$qb->join('c.language', 'l')
		   ->join("c.illustration", "il")
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
}