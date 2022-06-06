<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Comment;

/**
 * TelevisionSerieCommentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TelevisionSerieCommentRepository extends EntityRepository
{
	public function getShowComment($nbrMessageParPage, $page, $idClassName)
	{
		$premierMessageAafficher=($page-1)*$nbrMessageParPage;
	
		$qb = $this->createQueryBuilder('o');

		$qb->join('o.entity', 'a')
			->where('a.id = :idClassComment')
			->setParameter('idClassComment', $idClassName)
			->andWhere("o.state != :state")
			->setParameter("state", Comment::$denied)
			->orderBy('o.dateComment', 'DESC')
			->setFirstResult($premierMessageAafficher)
			->setMaxResults($nbrMessageParPage);
		
		return $qb->getQuery()->getResult();
	}
	
	public function countComment($idClassName)
	{
		$qb = $this->createQueryBuilder('c');
		$qb ->select("count(c)")
			->join('c.entity', 'a')
			->where('a.id = :idClassComment')
			->setParameter('idClassComment', $idClassName);

		return $qb->getQuery()->getSingleScalarResult();
	}
}