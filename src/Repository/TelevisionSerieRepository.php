<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * TelevisionSerieRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TelevisionSerieRepository extends MappedSuperclassBaseRepository
{
	public function getTelevisionSeries($datas, $locale)
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
		
		if(isset($datas["genre"]))
		{
			$qb->join('b.genre', 't')
			   ->andWhere("t.id = :genre")
			   ->setParameter("genre", $datas["genre"]->getId());
		}

		if(isset($datas["theme"])) {
			$qb->andWhere("b.theme = :themeId")
			   ->setParameter("themeId", $datas["theme"]);
		}

		return $qb->getQuery();
	}

	public function getByGenre($idGenre)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->innerjoin("b.genre", "p")
		   ->where("p.id = :idGenre")
		   ->setParameter("idGenre", $idGenre)
		   ->andWhere("b.archive = false");

		$qb->orderBy('b.title', 'ASC');

		return $qb->getQuery();
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

	public function getFileSelectorColorboxAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count = false)
	{
		return $this->getFileSelectorColorboxIllustrationAdmin($iDisplayStart, $iDisplayLength, $sSearch, $count);
	}

	public function getTelevisionSeriesByGenre($idGenre, $nbMessageByPage, $page)
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
}