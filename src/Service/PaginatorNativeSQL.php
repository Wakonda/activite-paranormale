<?php
namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaginatorNativeSQL
{
    /**
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $connection = null;
    /**
     * 
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;
    private $container;

    public function __construct(Connection $connection, RequestStack $requestStack, ContainerInterface $container)
    {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->container = $container;
    }

    function paginate($query, $page, $pagesize, $connection = null, $total = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($connection instanceof Connection) {
            $this->connection = $connection;
        }
        $offset = ($page - 1) * $pagesize;
        if(is_null($total)){
            $countQuery = preg_replace("/SELECT(.*)FROM/i", 'SELECT count(*) as total FROM', $query);
            $total = $this->connection->executeQuery($countQuery)->fetchColumn(0);
        }
        $query .= ' LIMIT ' . $offset . ',' . $pagesize;
        $list = $this->connection->executeQuery($query)->fetchAll();
        $slidingPagination = new SlidingPagination($request->query->all());
        $slidingPagination->setCurrentPageNumber($page);
        $slidingPagination->setItemNumberPerPage($pagesize);
        $slidingPagination->setItems($list);
        $slidingPagination->setPageRange(10);
        $slidingPagination->setUsedRoute($request->get('_route'));
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
        $slidingPagination->setTemplate($this->container->getParameter("knp_paginator.template.pagination"));
		
        return $slidingPagination;
    }
}