<?php


namespace App\Services;

use App\Repository\OrderWorkRepository;
use App\Repository\ProductionRepository;

use Carbon\Carbon;


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

    public function getOrdenesTrabajo(  $search = null, $vendorId = null)
    {
        // Lógica para crear una nueva orden de trabajo
        return $this->productionRepository->getOrdenesTrabajo($search, $vendorId);
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
    public function getOrderAsignados($vendorId = null)
    {
        // Lógica para obtener las órdenes de trabajo asignadas
        return $this->orderWorkRepository->getOrderAsignados($vendorId);
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

    // función para finalizar una orden de trabajo
    public function finalizarOT(
        int $id,
        string $startedAt,
        string $finishedAt,
        string $notes,
        int $userId
    ): void {

        $ot = $this->orderWorkRepository->findOrFail($id);

        if ($ot->status !== 'in_progress') {
            throw new \Exception('La orden de trabajo no está en ejecución');
        }

        $inicio = Carbon::parse($startedAt);
        $fin    = Carbon::parse($finishedAt);

        if ($fin->lessThanOrEqualTo($inicio)) {
            throw new \Exception('La fecha final debe ser mayor a la inicial');
        }

        // ⏱ TIEMPO REAL
        $totalMinutes = $inicio->diffInMinutes($fin);

        $this->orderWorkRepository->updateById($id, [
            'started_at'          => $inicio,
            'finished_at'         => $fin,
            'duration_minutes'    => $totalMinutes,
            'installation_notes'  => $notes,
            'usuario_finalizacion'=> $userId,
            'fechafinalizacion'   => $fin,
            'status'              => 'completed',
        ]);
    }

    // función para obtener el material de una orden de trabajo por ID
    public function getPedidoHgiPorOT(int $workOrderId)
    {
        return $this->orderWorkRepository->getPedidoHgiPorOT($workOrderId);
    }
}