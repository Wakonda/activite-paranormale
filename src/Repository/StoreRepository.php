<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * StoreRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StoreRepository extends EntityRepository
{
	// ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()>getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $type = null, $count = false)
	{
		$aColumns = ['c.id', 'c.title', 'c.platform', 'c.category', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		if(!empty($type) and $type != "Store") {
			$qb->andWhere("c INSTANCE OF App\Entity\Stores\\".$type."Store");
		} else {
			foreach($this->getClassMetadata()->subClasses as $subClass)
				$qb->andWhere("c NOT INSTANCE OF ".$subClass);
		}

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
	
	public function getAutocompleteBook($locale, $query)
	{
		$qb = $this->_em->createQueryBuilder();
		
		$qb->from("App\Entity\BookEdition", "pf")
		   ->innerjoin("pf.book", "b");
		
		if(!empty($locale))
		{
			$qb
			   ->select("CONCAT(b.title, ' - ', COALESCE(IF(pf.isbn13 IS NULL, pf.isbn10, pf.isbn13), '')) as text, b.title AS title, pf.id as id")
			   ->leftjoin("b.language", "la")
			   ->where('la.id = :locale')
			   ->setParameter('locale', $locale);
		}
		   
		if(!empty($query))
		{
			$query = is_array($query) ? "%".$query[0]."%" : "%".$query."%";
			$query = "%".$query."%";
			$qb->andWhere("b.title LIKE :query")
			   ->setParameter("query", $query);
		}
		
		$qb->orderBy("b.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
	}
	
	public function getAutocompleteAlbum($locale, $query)
	{
		$qb = $this->_em->createQueryBuilder();
		
		$qb->from("App\Entity\Album", "pf");
		
		if(!empty($locale))
		{
			$qb
			   ->select("CONCAT(pf.title, ' - ', ar.title) as text, pf.title as title, pf.id as id")
			   ->leftjoin("pf.language", "la")
			   ->leftjoin("pf.artist", "ar")
			   ->where('la.id = :locale')
			   ->setParameter('locale', $locale);
		}
		   
		if(!empty($query))
		{
			$query = is_array($query) ? "%".$query[0]."%" : "%".$query."%";
			$query = "%".$query."%";
			$qb->andWhere("pf.title LIKE :query")
			   ->setParameter("query", $query);
		}
		
		$qb->orderBy("pf.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
	}
	
	public function getAutocompleteMovie($locale, $query)
	{
		$qb = $this->_em->createQueryBuilder();
		
		$qb->from("App\Entity\Movies\Movie", "pf");
		
		if(!empty($locale))
		{
			$qb
			   ->select("CONCAT(pf.title, ' - ', pf.releaseYear) as text, pf.title as title, pf.id as id")
			   ->leftjoin("pf.language", "la")
			   ->where('la.id = :locale')
			   ->setParameter('locale', $locale);
		}
		   
		if(!empty($query))
		{
			$query = is_array($query) ? "%".$query[0]."%" : "%".$query."%";
			$query = "%".$query."%";
			$qb->andWhere("pf.title LIKE :query")
			   ->setParameter("query", $query);
		}
		
		$qb->orderBy("pf.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
	}
	
	public function getAutocompleteTelevisionSerie($locale, $query)
	{
		$qb = $this->_em->createQueryBuilder();
		
		$qb->from("App\Entity\Movies\TelevisionSerie", "pf");
		
		if(!empty($locale))
		{
			$qb
			   ->select("pf.title as text, pf.title as title, pf.id as id")
			   ->leftjoin("pf.language", "la")
			   ->where('la.id = :locale')
			   ->setParameter('locale', $locale);
		}
		   
		if(!empty($query))
		{
			$query = is_array($query) ? "%".$query[0]."%" : "%".$query."%";
			$query = "%".$query."%";
			$qb->andWhere("pf.title LIKE :query")
			   ->setParameter("query", $query);
		}
		
		$qb->orderBy("pf.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
	}
	
	public function getAutocompleteWitchcraftToolStore($locale, $query)
	{
		$qb = $this->_em->createQueryBuilder();
		
		$qb->from("App\Entity\WitchcraftTool", "pf");
		
		if(!empty($locale))
		{
			$qb
			   ->select("pf.title as title, pf.id as id")
			   ->leftjoin("pf.language", "la")
			   ->where('la.id = :locale')
			   ->setParameter('locale', $locale);
		}
		   
		if(!empty($query))
		{
			$query = is_array($query) ? "%".$query[0]."%" : "%".$query."%";
			$query = "%".$query."%";
			$qb->andWhere("pf.title LIKE :query")
			   ->setParameter("query", $query);
		}
		
		$qb->orderBy("pf.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
	}
	
	public function getStores($datas, $nbMessageByPage, $page, $locale)
	{
		$offset = ($page - 1) * $nbMessageByPage;

		$qb = $this->createQueryBuilder("b");
		$qb->innerjoin("b.language", "l")
		   ->where("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $locale)
		   ->orderBy("b.id", "DESC");

		if(isset($datas["category"]) and !empty($c = $datas["category"]))
		{
			$qb->andWhere("b.category = :category")
			   ->setParameter("category", $c);
		}

		if(isset($datas["platform"]) and !empty($c = $datas["platform"]))
		{
			$qb->andWhere("b.platform = :platform")
			   ->setParameter("platform", $c);
		}

		if(isset($datas["keywords"]) and !empty($c = $datas["keywords"])) {
			$fields = ["b.title", "b.text"];
			$orWhere = [];

			foreach($fields as $field)
				$orWhere[] = $field." LIKE :keyword";

			$qb->andWhere(implode(" OR ", $orWhere));
			$qb->setParameter("keyword", "%$c%");
		}

		$qb->setFirstResult($offset)
		   ->setMaxResults($nbMessageByPage);

		return $qb->getQuery();
	}

	public function getSlider($locale)
	{
		$qb = $this->createQueryBuilder('o');
		
		$qb->orderBy('o.id', 'DESC')
		   ->setMaxResults(5)
		   ->innerjoin("o.language", "l")
		   ->andWhere("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $locale)
		   ->orderBy("o.id", "DESC");

		return $qb->getQuery()->getResult();
	}

	public function getRandom($locale, Array $categories)
	{
		$qb = $this->createQueryBuilder("o");

		$qb->select("COUNT(o) AS countRow")
		   ->join('o.language', 'l')
		   ->where('l.abbreviation = :locale')
		   ->setParameter('locale', $locale);
		
		if(!empty($categories))
			$qb->andWhere("o.category IN (:categories)")
		       ->setParameter("categories", $categories);
		
		$max = max($qb->getQuery()->getSingleScalarResult() - 1, 0);
		$offset = rand(0, $max);

		$qb = $this->createQueryBuilder("o");

		$qb->join('o.language', 'l')
		   ->where('l.abbreviation = :locale')
		   ->setParameter('locale', $locale)
		   ->setFirstResult($offset)
		   ->setMaxResults(1);
		
		if(!empty($categories))
			$qb->andWhere("o.category IN (:categories)")
		       ->setParameter("categories", $categories);

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
		$res = [];
		
		foreach($entities as $entity)
		{
			$photo = new \StdClass();
			$photo->photo = $entity->getPhoto();
			$photo->path = $entity->getAssetImagePath();
			
			$res[] = $photo;
		}
		
		return $res;
	}
}