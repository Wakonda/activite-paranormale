<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\State;

/**
 * AdminUserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AdminUserRepository extends EntityRepository
{
	// Administration
	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		   ->where("c.enabled = true");

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getMembersUser()
	{
		$qb = $this->createQueryBuilder('o');
		$qb->where("o.enabled = true")
		   ->orderBy('o.id', 'DESC');

		return $qb->getQuery()->getResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = ['u.id', 'u.username', 'u.email', 'u.id', 'u.enabled', 'u.id'];

		$qb = $this->createQueryBuilder('u');
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
		
		if(!empty($searchByColumns))
		{
			$aSearchColumns = ['u.id', 'u.username', 'u.email', 'u.id', 'u.id'];
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
	
	public function getUsersContribution($user, $bundleClassName, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count, $displayState = 1)
	{
		$aColumns = ['m.title', 's.internationalName', 'l.abbreviation', 'm.writingDate'];

		$qb = $this->_em->createQueryBuilder();
		$qb	->from($bundleClassName, 'm')
			->innerjoin('m.language', 'l')
			->innerjoin('m.state', 's')
			->where('m.author = :author')
			->andWhere("s.internationalName IN (:states)")
			->setParameter('author', $user)
			->andWhere("m.archive = false");

		if($displayState == 1)
			$qb->setParameter("states", [State::$validate, State::$warned]);
		else if($displayState == 0)
			$qb->setParameter("states", [State::$draft, State::$writing, State::$waiting, State::$preview]);
		else if($displayState == -1)
			$qb->setParameter("states", [State::$duplicateValues, State::$refused]);

		if(is_array($sortByColumn) and is_array($sortDirColumn))
			$qb	->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$orWhere = [];
			
			foreach($aColumns as $column)
				$orWhere[] = $column." LIKE :search";

			$qb->andWhere(implode(" OR ", $orWhere))
			   ->setParameter('search', $search);
		}
		
		if(!$count)
		{
			$qb->select('m');
			$qb->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);
			return $qb->getQuery()->getResult();
		}
		else
		{
			$qb->select('COUNT(m)');
			return $qb->getQuery()->getSingleScalarResult();
		}
	}
	
	public function getUsersCommentContribution($user, $className, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $count = false)
	{
		$aColumns = ['c.messageComment', 'c.dateComment', 'c.id'];

		$qb = $this->_em->createQueryBuilder();
		
		$classNameQuery = $className ?? 'App\Entity\Comment';
		
		$qb	->from($classNameQuery, 'c')
			->where('c.authorComment = :author')
			->setParameter('author', $user);
		
		$classNameObject = new $classNameQuery();
		$mainEntity = (method_exists($classNameObject, "getMainEntityClassName")) ?? $classNameObject->getMainEntityClassName();

		if(!empty($className) and method_exists($mainEntity, "getArchive"))
			$qb->join("c.entity", "e")
		       ->andWhere("e.archive = false");

		if(is_array($sortByColumn) and is_array($sortDirColumn))
			$qb	->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$orWhere = [];
			
			foreach($aColumns as $column)
				$orWhere[] = $column." LIKE :search";

			$qb->andWhere(implode(" OR ", $orWhere))
			   ->setParameter('search', $search);
		}

		if(!$count)
		{
			$qb->select('c');
			$qb->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);
			return $qb->getQuery()->getResult();
		}
		else
		{
			$qb->select('COUNT(c)');
			return $qb->getQuery()->getSingleScalarResult();
		}
	}
	
	public function findUserByUsernameOrEmail($usernameOrEmail)
	{
		$qb = $this->createQueryBuilder('u');

		$qb ->where('u.usernameCanonical = :usernameOrEmail')
			->orWhere('u.emailCanonical = :usernameOrEmail')
			->setParameter('usernameOrEmail', $usernameOrEmail);
		
		return $qb->getQuery()->getOneOrNullResult();
	}
	
	public function findUserByEmail($email)
	{
		$qb = $this->createQueryBuilder('u');

		$qb ->where('u.emailCanonical = :email')
			->setParameter('email', $email);
		
		return $qb->getQuery()->getOneOrNullResult();
	}
	
	public function findUserByConfirmationToken($token)
	{
		$qb = $this->createQueryBuilder('u');

		$qb ->where('u.confirmationToken = :token')
			->setParameter('token', $token);
		
		return $qb->getQuery()->getOneOrNullResult();
	}
}