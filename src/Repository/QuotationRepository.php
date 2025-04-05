<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

use App\Entity\Quotation;

/**
 * QuotationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class QuotationRepository extends EntityRepository
{
	public function listeCitation($lang)
	{	
		$qb = $this->createQueryBuilder('o');

		$qb->join('o.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->orderBy('o.textQuotation');

		return $qb->getQuery()->getResult();
	}	
	
	public function randomQuote($lang)
	{
		$qbCount = $this->createQueryBuilder('o');

		$qbCount->select('COUNT(o)')
		        ->join('o.language', 'l')
			    ->where('l.abbreviation = :lang')
			    ->setParameter('lang', $lang)
				->andWhere("o.family != :poemFamily")
				->setParameter("poemFamily", Quotation::POEM_FAMILY);

		$max = max($qbCount->getQuery()->getSingleScalarResult() - 1, 0);
		$offset = rand(0, $max);

		$qb = $this->createQueryBuilder('o');

		$qb ->join('o.language', 'l')
			->where('l.abbreviation = :lang')
			->setParameter('lang', $lang)
			->andWhere("o.family != :poemFamily")
			->setParameter("poemFamily", Quotation::POEM_FAMILY)
			->setFirstResult($offset)
			->setMaxResults(1);
		
		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $family, $language, $datas = [], $count = false)
	{
		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("c.family = :family")
		   ->setParameter("family", $family);

		if(isset($datas["keywords"]) and !empty($keywords = $datas["keywords"])) {
			$search = "%".$keywords."%";
			$qb->andWhere('c.title LIKE :search OR c.textQuotation LIKE :search OR c.tags LIKE :search')
			   ->setParameter('search', $search);
		}

		if(isset($datas["country"]) and !empty($country = $datas["country"])) {
			
			$qb->join("c.country", "co")
			   ->andWhere('co.internationalName = :abbreviation')
			   ->setParameter('abbreviation', $country->getInternationalName());
		}

		if($family == Quotation::QUOTATION_FAMILY or $family == Quotation::POEM_FAMILY) {
			$aColumns = ['c.id', 'c.textQuotation', 'a.title'];
			$qb->join('c.authorQuotation', 'a');
		} elseif($family == Quotation::PROVERB_FAMILY) {
			$aColumns = ['c.id', 'c.textQuotation', 'a.title'];
			$qb->join('c.country', 'a');
		} elseif($family == Quotation::SAYING_FAMILY) {
			$aColumns = ['c.id', 'c.textQuotation', 'a.title'];
			$qb->join('c.country', 'a');
		} elseif($family == Quotation::HUMOR_FAMILY) {
			$aColumns = ['c.id', 'c.textQuotation'];
		} elseif($family == Quotation::LYRIC_FAMILY) {
			$qb->join("c.music", "m")
			   ->leftjoin("m.album", "a")
			   ->leftjoin("m.artist", "ar")
			   ->leftjoin("a.artist", "ara");

			$aColumns = ['c.id', 'c.textQuotation', 'm.musicPiece', 'IF(ara.id IS NOT NULL, ara.title, ar.title)'];
		}

		$qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		$query = [];
		   
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

	public function getDatatablesByCountryForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $countryId, $language, $family, $count = false)
	{
		$aColumns = ['c.textQuotation', null];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->andWhere("c.family = :family")
		   ->setParameter("family", $family)
		   ->andWhere("c.country = :countryId")
		   ->setParameter("countryId", $countryId);

		$qb->orderBy($aColumns[$sortByColumn[0]], $sortDirColumn[0]);

		$query = [];
		   
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

	public function countByFamily($language) {
		$qb = $this->createQueryBuilder('c');
		$qb->select("c.family")
		   ->addSelect("COUNT(c) As number")
		   ->join('c.language', 'l')
		   ->where('l.abbreviation = :language')
		   ->setParameter('language', $language)
		   ->groupBy("c.family");

		$res = [
			Quotation::QUOTATION_FAMILY => 0,
			Quotation::PROVERB_FAMILY => 0,
			Quotation::POEM_FAMILY => 0,
			Quotation::HUMOR_FAMILY => 0,
			Quotation::SAYING_FAMILY => 0
		];
		
		foreach($qb->getQuery()->getResult() as $data)
			$res[$data["family"]] = $data["number"];

		return $res;
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
		$aColumns = ['c.id', "IF(co.title IS NOT NULL, co.title, a.title)", 'c.textQuotation', 'l.title', 'c.family', 'c.id'];

		$qb = $this->createQueryBuilder('c');
		$qb->join('c.language', 'l')
		   ->leftjoin('c.authorQuotation', 'a')
		   ->leftjoin('c.country', 'co')
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
		if(is_null($entity->getAuthorQuotation()) or is_null($entity->getLanguage()))
			return 0;

		$qb = $this->createQueryBuilder("b");
		$qb->select("count(b)")
		   ->where("b.textQuotation = :textQuotation")
		   ->setParameter("textQuotation", $entity->getTextQuotation())
		   ->innerjoin("b.language", "l")
		   ->andWhere("l.abbreviation = :abbreviation")
		   ->setParameter("abbreviation", $entity->getLanguage()->getAbbreviation())
		   ->innerjoin("b.authorQuotation", "a")
		   ->andWhere("a.title = :authorQuotation")
		   ->setParameter("authorQuotation", $entity->getAuthorQuotation()->getTitle());
		   
		if($entity->getId() != null)
		{
		   $qb->andWhere("b.id != :id")
		      ->setParameter("id", $entity->getId());
		}
		
		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function getQuotationsByAuthor($biography, $language)
	{
		$qb = $this->createQueryBuilder('o');
		
		$qb->join('o.authorQuotation', 'b')
			->join('o.language', 'l')
			->where('l.abbreviation = :language')
			->setParameter('language', $language)
			->andWhere('b.id = :biography')
			->setParameter('biography', $biography->getId())
			->andWhere("o.family != :poemFamily")
			->setParameter("poemFamily", Quotation::POEM_FAMILY)
			->orderBy('o.id', 'DESC');

		return $qb->getQuery();
	}

	public function getSayingsByDateAndLocale($month, $day, $locale) {
		$qb = $this->createQueryBuilder('c');
		
		$qb->join('c.language', 'l')
		   ->where('l.abbreviation = :locale')
		   ->andWhere('c.date = :date')
		   ->setParameter('locale', $locale)
		   ->setParameter('date', $month."-".$day);
		   
		return $qb->getQuery()->getResult();
	}
}