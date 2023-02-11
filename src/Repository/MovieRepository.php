<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MovieRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MovieRepository extends MappedSuperclassBaseRepository
{
	
	public function getMovies($datas, $nbMessageByPage, $page, $locale)
	{
		$offset = ($page - 1) * $nbMessageByPage;

		$qb = $this->createQueryBuilder("b");
		$qb->innerjoin("b.language", "l")
		   ->where("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $locale)
		   ->andWhere("b.archive = false");

		if(isset($datas["sort"]))
		{
			$sort = explode("#", $datas["sort"]);
			$qb->orderBy("b.".$sort[0], $sort[1]);
		}
		
		if(isset($datas["keywords"]))
		{
			$qb->andWhere("(b.title LIKE :keyword OR b.text LIKE :keyword)")
			   ->setParameter("keyword", "%".$datas["keywords"]."%");
		}
		
		if(isset($datas["genre"]))
		{
			$qb->join('b.genre', 't')
			   ->andWhere("t.id = :genre")
			   ->setParameter("genre", $datas["genre"]->getId());
		}
		
		if(isset($datas["releaseYear"]))
		{
			$qb->andWhere("b.releaseYear = :releaseYear")
			   ->setParameter("releaseYear", $datas["releaseYear"]);
		}

		$qb->orderBy('b.writingDate', 'DESC')
		   ->setFirstResult($offset)
		   ->setMaxResults($nbMessageByPage);

		return $qb->getQuery();
	}

	public function getMoviesByGenre($idGenre, $nbMessageByPage, $page)
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

	public function countMovieByLanguage($language)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		   ->join("c.language", "l")
		   ->where("l.abbreviation = :language")
		   ->setParameter("language", $language)
		   ->andWhere("c.archive = false");

		return $qb->getQuery()->getSingleScalarResult();
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
		$qb = $this->createQueryBuilder("b");
		$qb->select("count(b)")
		   ->innerjoin("b.language", "l")
		   ->andWhere("l.abbreviation = :abbreviation")
		   ->setParameter("title", $entity->getTitle())
		   ->setParameter("abbreviation", $entity->getLanguage()->getAbbreviation());
		   
		$orWhere = [];
		
		$orWhere[] = "b.title = :title";

		if(!empty($d = $entity->getWikidata())) {
			$orWhere[] = "b.wikidata = :wikidata";
			$qb->setParameter("wikidata", $d);
		}
		if(!empty($is = $entity->getIdentifiers())) {
			foreach(json_decode($is) as $key => $identifier) {
				
			$orWhere[] = "(JSON_SEARCH(b.identifiers, 'one', :identifier{$key}, NULL, '$[*].identifier') IS NOT NULL AND JSON_SEARCH(b.identifiers, 'one', :value{$key}, NULL, '$[*].value') IS NOT NULL)";
			$qb->setParameter("identifier{$key}", $identifier->identifier)
			   ->setParameter("value{$key}", $identifier->value);
			}
		}
		
		$qb->andWhere(implode(" OR ", $orWhere));

		if($entity->getId() != null)
		{
		   $qb->andWhere("b.id != :id")
		      ->setParameter("id", $entity->getId());
		}

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->orderBy('c.publicationDate', 'DESC')
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
	
	public function getAutocomplete($locale, $query, $id = null)
	{
		$qb = $this->createQueryBuilder("pf");
		
		if(!empty($locale))
		{
			$qb->leftjoin("pf.language", "la")
			   ->where('la.abbreviation = :locale')
			   ->setParameter('locale', $locale);
		}
		   
		if(!empty($query))
		{
			$query = is_array($query) ? "%".$query[0]."%" : "%".$query."%";
			$query = "%".$query."%";
			$qb->andWhere("pf.title LIKE :query")
			   ->setParameter("query", $query);
		}
		
		if(!empty($id)) {
			$qb->andWhere("pf.id != :id")
			   ->setParameter("id", $id);
		}
		
		$qb->orderBy("pf.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
	}
	
	public function getFirstFilmSeries($entity)
	{
		
		if(empty($entity->getPrevious()))
			return $entity;
		
		return $this->getFirstFilmSeries($entity->getPrevious());
	}
	
	public function getFilmSeries($entity, Array &$movies): Array
	{
		$qb = $this->createQueryBuilder("pf");
		
		$qb->where("pf.previous = :previousId")
		   ->setParameter("previousId", $entity->getId());
		   
		$previous = $qb->getQuery()->getOneOrNullResult();
		
		if(empty($previous))
			return $movies;
			
		$movies[] = $previous;
		
		return $this->getFilmSeries($previous, $movies);
	}
}