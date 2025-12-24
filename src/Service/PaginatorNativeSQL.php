<?php
namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaginatorNativeSQL
{
    private $connection = null;
    private $requestStack;
	private $parameterBag;

    public function __construct(Connection $connection, RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
    }

    function paginate($query, $page, $pagesize, $connection = null, $total = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        // if ($connection instanceof Connection) {
            $this->connection = $connection;
        // }
        $offset = ($page - 1) * $pagesize;
        if(is_null($total)){
            $countQuery = preg_replace("/SELECT(.*)FROM/i", 'SELECT count(*) as total FROM', $query);
			   $stmt = $this->connection->query($countQuery);
            $total = $stmt->fetchColumn();
        }
        $query .= ' LIMIT ' . $pagesize . ' OFFSET ' . $offset;

		$stmt = $this->connection->query($query);
		$list = $stmt->fetchAll();

        $slidingPagination = new SlidingPagination($request->query->all());
        $slidingPagination->setCurrentPageNumber($page);
        $slidingPagination->setItemNumberPerPage($pagesize);
        $slidingPagination->setItems($list);
        $slidingPagination->setPageRange(10);
        $slidingPagination->setUsedRoute($request->attributes->get('_route'));
        $slidingPagination->setTotalItemCount($total);
        $slidingPagination->setPaginatorOptions([
            "pageParameterName" => "page",
            "sortFieldParameterName" => "sort",
            "sortDirectionParameterName" => "direction",
            "filterFieldParameterName" => "filterField",
            "filterValueParameterName" => "filterValue",
            "distinct" => true
        ]);

        $slidingPagination->setCustomParameters([]);
        $slidingPagination->setTemplate($this->parameterBag->get("knp_paginator.template.pagination"));
		
        return $slidingPagination;
    }
}