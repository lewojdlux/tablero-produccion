<?php


namespace App\Services;

use App\Repository\OrderWorkRepository;
use App\Repository\ProductionRepository;




class OrderWorkService
{

    protected OrderWorkRepository $orderWorkRepository;
    protected ProductionRepository $productionRepository;

    public function __construct(OrderWorkRepository $orderWorkRepository, ProductionRepository $productionRepository)
    {
        $this->orderWorkRepository = $orderWorkRepository;
        $this->productionRepository = $productionRepository;
    }

    /* Métodos para acceder a datos relacionados con órdenes de trabajo */
    public function getAllOrders()
    {
        // Lógica para obtener todas las órdenes de trabajo
        return $this->orderWorkRepository->getAllOrders();
    }

    public function getOrdenesTrabajo()
    {
        // Lógica para crear una nueva orden de trabajo
        return $this->productionRepository->getOrdenesTrabajo();
    }

    public function getOrderDetail($ndoc)
    {
        // Lógica para obtener el detalle de una orden de trabajo por número de documento
        return $this->productionRepository->getOrderDetailDocumento($ndoc, $producto = '');
    }

    public function createOrderWork(array $data)
    {
        // Lógica para crear una nueva orden de trabajo
        return $this->orderWorkRepository->createOrderWork($data);
    }

    /* función para obtener las órdenes de trabajo asignadas */
    public function getOrderAsignados()
    {
        // Lógica para obtener las órdenes de trabajo asignadas
        return $this->orderWorkRepository->getOrderAsignados();
    }

    public function getMaterialsByOrderId($orderId)
    {
        // Lógica para obtener los materiales de una orden de trabajo por ID
        return $this->orderWorkRepository->getMaterialsByOrderId($orderId);
    }

    public function getMaterialsByMaterialName($materialName)
    {
        // Lógica para buscar materiales por nombre
        return $this->orderWorkRepository->getMaterialsByMaterialName($materialName);
    }
}