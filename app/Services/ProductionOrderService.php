<?php

namespace App\Services;

use App\Repository\ProductionOrderRepository;
use App\Repository\ProductionRepository;

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

    // función para obtener el listado de materiales disponibles (para autocompletar en el formulario)
    public function getAllMaterials()
    {
        return $this->repository->buscarMateriales();
    }
}
