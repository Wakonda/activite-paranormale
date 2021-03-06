<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * AlbumRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AlbumRepository extends EntityRepository
{
	// ****************** ADMINISTRATION
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 'c.genre', 'c.website', 'l.title', 'c.id');

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
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

	public function getDatatablesForIndexByArtistAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $artistId, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 'c.genre', 'c.website', 'l.title', 'c.id');

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->where("c.artist = :artistId")
		   ->setParameter("artistId", $artistId)
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
	
	public function getMusicsByAlbum($id)
	{
		$qb = $this->_em->createQueryBuilder();

		$qb->select("al.id AS album_id, al.title AS album_title, ar.id AS artist_id, ar.title AS artist_title, COUNT(al.id) AS number_title")
		   ->from("App\Entity\Music", "mu")
		   ->leftjoin("mu.album", "al")
		   ->leftjoin("al.artist", "ar")
		   ->where("ar.id = :id")
		   ->setParameter("id", $id)
		   ->groupby("al.id");

		return $qb->getQuery()->getResult();
	}
	
	public function getAutocomplete($locale, $query)
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

		$qb->orderBy("pf.title", "ASC")
		   ->setMaxResults(15);

		return $qb->getQuery()->getResult();
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