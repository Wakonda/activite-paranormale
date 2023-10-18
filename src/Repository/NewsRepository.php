<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * NewsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NewsRepository extends MappedSuperclassBaseRepository
{
	public function getNews($theme, $lang)
	{
		$qb = $this->createQueryBuilder('o');
		
		$qb->join('o.state', 's')
			->join('o.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $lang)
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

	public function getArchive($nbrMessageParPage, $page, $lang)
	{	
		$premierMessageAafficher=($page-1)*$nbrMessageParPage;
		$queryBuilder = $this->createQueryBuilder('o');
		
		$queryBuilder->join('o.state', 's')
					->join('o.language', 'l')
					->where('l.abbreviation = :lang')
					->setParameter('lang', $lang)
					->andWhere('s.displayState = 1')
					->andWhere('o.archive = 1')
					->orderBy('o.publicationDate', 'DESC')
					->setFirstResult($premierMessageAafficher)
					->setMaxResults($nbrMessageParPage);

		return $queryBuilder->getQuery()->getResult();
	}
	
	public function getEntitiesArchivedPagination($page, $theme, $locale)
	{
		$qb = $this->createQueryBuilder('o');

		$qb->join('o.state', 's')
			->join('o.language', 'l')
			->where('l.abbreviation = :locale')
			->setParameter('locale', $locale)
			->andWhere('s.displayState = 1')
			->andWhere('o.archive = 1')
			->orderBy('o.publicationDate', 'DESC');

		if(!empty($theme))
		{
			$qb->join('o.theme', 't')
				->andWhere('t.title = :theme')
				->setParameter('theme', $theme);
		}

		return $qb->getQuery();
	}
	
	public function nbrArchiveParTheme($lang, $theme)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->select("count(o)")
			->join('o.language', 'l')
			->join('o.theme', 't')
			->join('o.state', 's')
			->where('s.displayState = 1')
			->andWhere('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->andWhere('t.title = :theme')
			->setParameter('theme', $theme)
			->andWhere('o.archive = true');

		return $qb->getQuery()->getSingleScalarResult();	
	}
	
	public function countEntreeArchive($theme, $lang)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
			->join('c.language', 'l')
			->join('c.theme', 't')
			->join('c.state', 's')
			->where('s.displayState = 1')
			->andWhere('t.title = :theme')
			->setParameter('theme', $theme)
			->andWhere('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->andWhere('c.archive = true');
			
		return $qb->getQuery()->getSingleScalarResult();
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
		   ->leftjoin("n.state", "s")
		   ->andWhere("s.displayState = 1")
		   ->setMaxResults(1)
		   ->andWhere('n.archive = false');

		$previousEntity = $qb->getQuery()->getOneOrNullResult();

		$qb = $this->createQueryBuilder('n');
		$qb->orderBy('n.publicationDate', 'DESC')->orderBy('n.id', 'ASC')
		   ->where('n.publicationDate >= :publicationDate')->andWhere('n.id != '.$entity->getId())
		   ->setParameter('publicationDate', $entity->getPublicationDate())
		   ->join('n.language', 'l')
		   ->andWhere('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->leftjoin("n.state", "s")
		   ->andWhere("s.displayState = 1")
		   ->setMaxResults(1)
		   ->andWhere('n.archive = false');

		$nextEntity = $qb->getQuery()->getOneOrNullResult();

		return ["previous" => $previousEntity, "next" => $nextEntity];
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

	public function getSliderNew()
	{
		$qb = $this->createQueryBuilder('o');
		
		$qb->join('o.state', 's')
		   ->andWhere('s.displayState = 1')
		   ->orderBy('o.id', 'DESC')
		   ->setMaxResults(5)
		   ->andWhere('o.archive = false');

		return $qb->getQuery()->getResult();
	}
	
	public function getMainSliderNew($lang)
	{	
		$qb = $this->createQueryBuilder('o');

		$qb->join('o.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->join('o.state', 's')
			->andWhere('s.displayState = 1')
			->andWhere('o.archive = 0')
			->orderBy('o.publicationDate', 'DESC')
			->setMaxResults(5);

		return $qb->getQuery()->getResult();
	}

	public function readArticle($archiver, $lang, $id)
	{	
		$qb = $this->createQueryBuilder('o');

		$qb->join('o.language', 'l')
			->where('o.archive = :archiver')
			->setParameter('archiver', $archiver)
			->join('o.state', 's')
			->andWhere('s.displayState = 1')
			->andWhere('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->andWhere('o.id = :id')
			->setParameter('id', $id);
		
		return $qb->getQuery()->getResult();
	}
	
	//News of the world
	public function countWorldNews()
	{
		$qb = $this->createQueryBuilder('o');
		$qb->select('COUNT(o)')
		   ->leftjoin('o.language', 'l')
		   ->andWhere('o.archive = false')
		   ->leftjoin('o.state', 's')
		   ->andWhere('s.displayState = 1');

		$currentLanguages = $this->currentLanguages();
		$whereIn = [];
		for($i = 0; $i < count($currentLanguages); $i++)
		{
			$whereIn[] = ':'.$currentLanguages[$i];
			$qb->setParameter(':'.$currentLanguages[$i], $currentLanguages[$i]);
		}

		$qb->andWhere('l.abbreviation NOT IN ('.implode(", ", $whereIn).')');
		
		return $qb->getQuery()->getSingleScalarResult();
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

	public function searchData($nbrMessageByPage, $page, $query)
	{
		$premierMessageAafficher=($page-1)*$nbrMessageByPage;
		$qb = $this->createQueryBuilder('o');
		
		$nameFilter = '%'.$query.'%';
		$qb = $this->createQueryBuilder('n');
		$qb->where('n.title LIKE :nameFilter')
		->setParameter('nameFilter', $nameFilter)
		->setFirstResult($premierMessageAafficher)
		->setMaxResults($nbrMessageByPage);
		return $qb->getQuery()->getResult();
	}
	
	public function countSearchData($query)
	{
		$nameFilter = '%'.$query.'%';
		$qb = $this->createQueryBuilder('n');
		$qb->select("count(n)")
			->where('n.title LIKE :nameFilter')
			->setParameter('nameFilter', $nameFilter);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function countNewsByStateAdmin($state)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		->innerjoin('c.state', 's')
		->where('s.internationalName = :state')
		->setParameter('state', $state);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getNewsByStateAdmin($state)
	{
		$qb = $this->createQueryBuilder('o');
		$qb->innerjoin('o.state', 's')
		->where('s.internationalName = :state')
		->setParameter('state', $state);

		return $qb->getQuery()->getResult();
	}
	// Author
	public function getArticleByAuthor($user, $draft=0)
	{
		$qb = $this->createQueryBuilder('a');
		$qb->orderBy('a.publicationDate')
		   ->join('a.state', 's')
		   ->join('a.author', 'u')
		   ->where('u.username = :user')
		   ->setParameter('user', $user);
		   
		if($draft == 0)
		{
			$qb->andWhere('s.displayState = 1');
		}
		else
		{
			$qb->andWhere('s.title = :title')
			->setParameter('title', 'Brouillon');
		}
		return $qb->getQuery()->getResult();
	}

	public function getDatatables($count, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn)
	{
		$aColumns = ['a.id', 'a.title', 'a.publicationDate', 'a.author', 'a.theme', 'a.archive', 'a.language', 'a.title'];
		$qb = $this->createQueryBuilder('a');
			
		if($count)
		{
			$qb->select('COUNT(a)');
			return $qb->getQuery()->getSingleScalarResult();
		}
			
		if($iDisplayLength != 0)
		{
			$qb->setFirstResult($iDisplayStart)
			 ->setMaxResults($iDisplayLength);
		}
			
		if($sortByColumn != "")
			$qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);
			
		return $qb->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 'c.publicationDate', 'c.pseudoUsed', 't.title', 's.title', 'l.title', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb
		   ->join('c.language', 'l')
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
		
		if(!empty($searchByColumns))
		{
			$aSearchColumns = ['c.id', 'c.title', 'c.publicationDate', 'c.pseudoUsed', 't.internationalName', 's.internationalName', 'l.id', 'c.id'];//dd($aSearchColumns, $searchByColumns);
			foreach($aSearchColumns as $i => $aSearchColumn)
			{
				if(!empty($searchByColumns[$i]))
				{
					$search = "%".$searchByColumns[$i]["value"]."%";
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

	public function getDatatablesForWorldIndex($language, $theme, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');

		$aColumns = ['l.abbreviation', 'il.titleFile', 'c.title', 'c.publicationDate'];

		$qb->join('c.language', 'l')
		   ->join('c.illustration', 'il')
		   ->where('c.archive = false')
		   ->leftjoin('c.state', 's')
		   ->andWhere('s.displayState = 1');
		   
		if(!empty($theme))
		    $qb->andWhere('c.theme = :themeId')
		       ->setParameter("themeId", $theme);
	
		if($language == "all")
		{
			$currentLanguages = $this->currentLanguages();
			$whereIn = [];
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
	
	public function countAllEntitiesArchivedPublicationByTheme($theme)
	{
		$qb = $this->createQueryBuilder('c');

		$qb->select('count(c)')
		   ->join('c.theme', 't')
		   ->join('c.state', 's')
		   ->andWhere('s.displayState = 1')
		   ->andWhere('t.id = :theme')
		   ->andWhere('c.archive = true')
		   ->setParameter('theme', $theme);

		return $qb->getQuery()->getSingleScalarResult();
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
		   ->andWhere('c.archive = :archive')
		   ->setParameter('archive', $entity->getArchive())
		   ->orderBy('c.id', 'desc')
		   ->setMaxResults(3);

		return $qb->getQuery()->getResult();
	}
}