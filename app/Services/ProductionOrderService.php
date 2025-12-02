<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Repository\ProductionRepository;
use App\Repository\ProductionOrderRepository;

class ProductionOrderService

{
    protected $repository;
    protected $productionOrderRepository;

    public function __construct(ProductionRepository $repository, ProductionOrderRepository $productionOrderRepository)
    {
        $this->repository = $repository;
        $this->productionOrderRepository = $productionOrderRepository;
    }


    public function getSearchOrders(array $filters)
    {
        return $this->repository->searchOrders($filters);
    }



    /*
        * Listar órdenes de producción en mysql
        *
    */
    public function listProductionOrders(array $filters): array
    {
        return $this->productionOrderRepository->listOrders($filters);
    }


    public function getSearchProductionOrders(array $filters)
    {
        return $this->productionOrderRepository->getOrdersForTable($filters);
    }

















































}