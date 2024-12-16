<?php

namespace App\Repository;

use App\Entity\DocumentFamily;

/**
 * DocumentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DocumentRepository extends MappedSuperclassBaseRepository
{
	public function getDatatablesForIndex($locale, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $datas = [], $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		
		$qb->join("c.language", "l")
		   ->join("c.documentFamily", "co")
		   ->leftjoin("c.authorDocumentBiographies", "ad");

		$subquery = $this->_em->createQueryBuilder()
			->select('dc.title')
			->from(DocumentFamily::class, 'dc')
			->join("dc.language", "dcl")
			->where('dc.internationalName = co.internationalName')
			->andWhere("dcl.abbreviation = :locale");
		
		if(!$count)
			$qb->addSelect("(".$subquery->getQuery()->getDQL().") AS HIDDEN documentFamilyTitle")
			   ->setParameter("locale", $locale);

		$aColumns = ['c.title', 'ad.title', 'c.releaseDateOfDocument', 'documentFamilyTitle', 'l.title'];

		if(!empty($title = $datas["title"])) {
			$search = "%".$title."%";
			$qb->andWhere('c.title LIKE :search')
			   ->setParameter('search', $search);
		}
		if(!empty($documentFamily = $datas["documentFamily"])) {
			$qb->andWhere('co.internationalName = :internationalName')
			   ->setParameter('internationalName', $documentFamily->getInternationalName());
		}
		if(!empty($theme = $datas["theme"])) {
			$qb->join("c.theme", "t")
		       ->andWhere('t.internationalName = :internationalName')
			   ->setParameter('internationalName', $theme->getInternationalName());
		}

		if(!empty($sSearch))
		{
			$search = "%".$sSearch."%";
			$qb->andWhere('c.title LIKE :search')
			   ->setParameter('search', $search);
		}
		if($count)
		{
			$qb->select("count(c)");
			return $qb->getQuery()->getSingleScalarResult();
		}
		else {
			if(!empty($sortDirColumn))
			   $qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);
			$qb->setFirstResult($iDisplayStart)->setMaxResults($iDisplayLength);
		}

		return $qb->getQuery()->getResult();
	}

	public function countDocument()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)")
		   ->andWhere("c.archive = false");
		return $qb->getQuery()->getSingleScalarResult();
	}

	// ADMINISTRATION
	public function countArchivedEntries()
	{
		return $this->countArchivedEntriesGeneric($this->createQueryBuilder('c'));
	}

	public function countAdmin()
	{
		$qb = $this->createQueryBuilder('c');
		$qb->select("count(c)");
		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $count = false)
	{
		$aColumns = array( 'c.id', 'c.title', 'c.id', 'l.title', 'c.id');

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

	public function getDocumentsByBiographyInternationalName($internationalName)
	{
		$qb = $this->createQueryBuilder("d");
		
		$qb->leftjoin('d.authorDocumentBiographies', 'b')
		   ->where('b.internationalName = :internationalName')
		   ->setParameter('internationalName', $internationalName);

		return $qb->getQuery()->getResult();
	}

	public function getDocumentsByTheme($themeId)
	{
		$qb = $this->createQueryBuilder("d");
		   
		$qb->andWhere("d.archive = false");
		
		if(!empty($themeId))
			$qb->where('d.theme = :themeId')
			   ->setParameter('themeId', $themeId);

		return $qb->getQuery()->getResult();
	}
}