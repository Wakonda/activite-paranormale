<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * BookEditionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BookEditionRepository extends EntityRepository
{
	public function countForDoublons($entity)
	{
		$qb = $this->createQueryBuilder("b");
		$qb->select("count(b)")
		   ->where("b.book = :id")
		   ->setParameter("id", $entity->getBook()->getId())
		   ->andWhere("b.isbn10 = :isbn10")
		   ->setParameter("isbn10", $entity->getIsbn10());
		   
		if($entity->getId() != null)
		{
		   $qb->andWhere("b.id != :id")
		      ->setParameter("id", $entity->getId());
		}
		
		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, Int $bookId, bool $count = false)
	{
		$aColumns = array( 'p.title', 'u.isbn10', 'u.isbn13', 'u.id');

		$qb = $this->createQueryBuilder('u');
		$qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0])
		   ->join("u.publisher", "p")
		   ->where("u.book = :bookId")
		   ->setParameter("bookId", $bookId);

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
			$aSearchColumns = array('u.isbn10', 'u.isbn13', 'p.title', 'u.id');
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
			$qb->select("count(u)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else
			$qb->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);

		return $qb->getQuery()->getResult();
	}

	public function getBooksByPublisher($idPublisher, $nbMessageByPage, $page)
	{
		$offset = ($page - 1) * $nbMessageByPage;
		
		$qb = $this->createQueryBuilder("be");
		$qb->innerjoin("be.book", "b")
		   ->innerjoin("be.publisher", "p")
		   ->where("p.id = :idPublisher")
		   ->setParameter("idPublisher", $idPublisher)
		   ->andWhere("b.archive = false");

		$qb->orderBy('b.writingDate', 'DESC')
		   ->setFirstResult($offset)
		   ->setMaxResults($nbMessageByPage);

		return $qb->getQuery();
	}
}