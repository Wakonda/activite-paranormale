<?php

namespace App\Repository;

use App\Entity\LoginHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LoginHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginHistory::class);
    }

    public function countRecentFailuresByIp(string $ip, int $minutes = 15): int
    {
        return (int) $this->createQueryBuilder('lh')
            ->select('COUNT(lh.id)')
            ->where('lh.ipAddress = :ip')
            ->andWhere('lh.success = false')
            ->andWhere('lh.createdAt >= :since')
            ->setParameter('ip', $ip)
            ->setParameter('since', new \DateTimeImmutable("-{$minutes} minutes"))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countRecentFailuresByIdentifier(string $identifier, int $minutes = 15): int
    {
        return (int) $this->createQueryBuilder('lh')
            ->select('COUNT(lh.id)')
            ->where('lh.attemptedIdentifier = :identifier')
            ->andWhere('lh.success = false')
            ->andWhere('lh.createdAt >= :since')
            ->setParameter('identifier', $identifier)
            ->setParameter('since', new \DateTimeImmutable("-{$minutes} minutes"))
            ->getQuery()
            ->getSingleScalarResult();
    }

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.attemptedIdentifier', 'c.succes', 'c.ipAddress', 'c.createdAt', 'c.id');

		$qb = $this->createQueryBuilder('c');
		$qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

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
}