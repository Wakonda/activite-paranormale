<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MusicRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MusicRepository extends EntityRepository
{
	public function getArtistMusic()
    {
        $qb = $this->createQueryBuilder('a');
        $qb ->join('a.artist', 't');
        return $qb->getQuery()->getResult();
    }
	
	public function countEntreeMusique($id)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
			->join('c.artist', 'o')
			->where('o.id = :id')
			->setParameter('id', $id);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getTabMusic($nbrMessageParPage, $page, $id)
	{	
		$premierMessageAafficher=($page-1)*$nbrMessageParPage;
		$queryBuilder = $this->createQueryBuilder('a');

		$queryBuilder->join('a.artist', 'o')
					->where('o.id = :id')
					->setParameter('id', $id)
					->orderBy('a.musicPiece')
					->setFirstResult($premierMessageAafficher)
					->setMaxResults($nbrMessageParPage);

		return $queryBuilder->getQuery()->getResult();
	}	
	
	public function countMusic(String $language)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		   ->join('c.album', 'al')
		   ->join('al.artist', 'a')
		   ->join('a.language', 'l')
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getMusicsByArtist($id)
	{
		$qb = $this->createQueryBuilder("m");

		$qb->leftjoin("m.artist", "a")
		   ->where("a.id = :id")
		   ->setParameter("id", $id);

		return $qb->getQuery()->getResult();
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
		$aColumns = ['c.id', 'a.title', 'c.musicPiece', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.album', 'al')
		   ->join('al.artist', 'a')
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

	public function getDatatablesForIndexByAlbumAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $albumId, $count = false)
	{
		$aColumns = ['c.musicPiece', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.album', 'al')
		   ->join('al.artist', 'a')
		   ->where("al.id = :albumId")
		   ->setParameter("albumId", $albumId)
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
		   ->where("b.musicPiece = :musicPiece")
		   ->setParameter("musicPiece", $entity->getMusicPiece())
		   ->innerjoin("b.album", "a")
		   ->innerjoin("a.language", "l")
		   ->andWhere("a.title = :title")
		   ->setParameter("title", $entity->getAlbum()->getTitle())
		   ->andWhere("l.abbreviation = :locale")
		   ->setParameter("locale", $entity->getAlbum()->getLanguage()->getAbbreviation());
		   
		if($entity->getId() != null)
		{
		   $qb->andWhere("b.id != :id")
		      ->setParameter("id", $entity->getId());
		}
		
		return $qb->getQuery()->getSingleScalarResult();
	}
}