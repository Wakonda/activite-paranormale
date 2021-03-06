<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MovieVoteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MovieVoteRepository extends EntityRepository
{
	public function averageVote($className, $idClassName)
	{	
		$qb = $this->createQueryBuilder('o');

		$qb ->select('avg(o.valueVote)')
			->join('o.entity', 'a')
			->where('a.id = :idClassComment')
			->setParameter('idClassComment', $idClassName);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function countVoteByClassName($className, $idClassName)
	{	
		$qb = $this->createQueryBuilder('c');

		$qb ->select('count(c.valueVote)')
			->join('c.entity', 'a')
			->where('a.id = :idClassComment')
			->setParameter('idClassComment', $idClassName);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getAverageVotesByArticle($entity)
	{
		$qb = $this->_em->createQueryBuilder();
		$qb->select('AVG(v.valueVote) AS ratingAverage')
		   ->from('App\Entity\MovieVote', 'v')
		   ->where('v.entity = :entityId')
		   ->setParameter('entityId', $entity->getId());
	   
		$result = $qb->getQuery()->getSingleResult();

		if(!is_array($result) or empty($result['ratingAverage']))
			return '-';
		
		return $result['ratingAverage'];
	}
}