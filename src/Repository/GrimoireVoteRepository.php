<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * GrimoireVoteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GrimoireVoteRepository extends EntityRepository
{
	public function averageVote($className, $idClassName)
	{	
		$qb = $this->createQueryBuilder('o');

		$qb ->select('avg(o.valueVote)')
			->join('o.grimoire', 'a')
			->where('a.id = :idClassComment')
			->setParameter('idClassComment', $idClassName);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function countVoteByClassName($className, $idClassName)
	{	
		$qb = $this->createQueryBuilder('c');

		$qb ->select('count(c.valueVote)')
			->join('c.grimoire', 'a')
			->where('a.id = :idClassComment')
			->setParameter('idClassComment', $idClassName);

		return $qb->getQuery()->getSingleScalarResult();
	}
}