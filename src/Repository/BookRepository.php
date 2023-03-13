<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * BookRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BookRepository extends MappedSuperclassBaseRepository
{
	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 'l.title', 'c.id');

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
		$authorIds = array_map(function($e) { return $e->getId(); }, $entity->getAuthors()->getValues());

		$qb = $this->createQueryBuilder("b");
		$qb->select("count(b)")
		   ->where("b.title = :title")
		   ->setParameter("title", $entity->getTitle())
		   ->innerjoin("b.language", "l")
		   ->andWhere("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $entity->getLanguage()->getAbbreviation())
		   ->innerJoin('b.authors', 'aths')
		   ->andWhere("aths.id IN (:authorIds)")
		   ->setParameter("authorIds", $authorIds);

		if($entity->getId() != null)
		{
		   $qb->andWhere("b.id != :id")
		      ->setParameter("id", $entity->getId());
		}

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getBooks($datas, $locale)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->innerjoin("b.language", "l")
		   ->where("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $locale)
		   ->andWhere("b.archive = false");

		if(isset($datas["sort"]))
		{
			$sort = explode("#", $datas["sort"]);
			$qb->orderBy("b.".$sort[0], $sort[1]);
		} else
			$qb->orderBy('b.writingDate', 'DESC');

		if(isset($datas["keywords"]))
		{
			$qb->andWhere("(b.title LIKE :keyword OR b.text LIKE :keyword)")
			   ->setParameter("keyword", "%".$datas["keywords"]."%");
		}

		if(isset($datas["theme"]))
		{
			$qb->join('b.theme', 't')
			   ->andWhere("t.id = :theme")
			   ->setParameter("theme", $datas["theme"]->getId());
		}

		if(isset($datas["genre"]))
		{
			$qb->join('b.genre', 'g')
			   ->andWhere("g.id = :genre")
			   ->setParameter("genre", $datas["genre"]->getId());
		}

		return $qb->getQuery();
	}

	public function getBooksByGenre($idGenre, $nbMessageByPage, $page)
	{
		$offset = ($page - 1) * $nbMessageByPage;
		
		$qb = $this->createQueryBuilder("b");
		$qb->innerjoin("b.genre", "p")
		   ->where("p.id = :idGenre")
		   ->setParameter("idGenre", $idGenre)
		   ->andWhere("b.archive = false");

		$qb->orderBy('b.title', 'ASC')
		   ->setFirstResult($offset)
		   ->setMaxResults($nbMessageByPage);

		return $qb->getQuery();
	}

	public function getBooksByBiographyInternationalName($internationalName)
	{
		$qb = $this->createQueryBuilder("d");
		
		$qb->leftjoin('d.authors', 'b')
		   ->leftjoin('d.fictionalCharacters', 'f')
		   ->where('b.internationalName = :internationalName')
		   ->orWhere('f.internationalName = :internationalName')
		   ->setParameter('internationalName', $internationalName);

		return $qb->getQuery()->getResult();
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

	public function countByLanguage($language)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		   ->join("c.language", "l")
		   ->where("l.abbreviation = :language")
		   ->setParameter("language", $language)
		   ->andWhere("c.archive = false");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->orderBy('c.id', 'DESC')
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
		$res = [];
		
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