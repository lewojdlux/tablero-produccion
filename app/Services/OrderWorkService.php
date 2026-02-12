<?php


namespace App\Services;

use App\Repository\OrderWorkRepository;
use App\Repository\ProductionRepository;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        string $finishedAt,
        string $notes,
        int $userId
    ): void {

        $ot = $this->orderWorkRepository->findOrFail($id);

        if ($ot->status !== 'in_progress') {
            throw new \Exception('La orden de trabajo no está en ejecución');
        }

        $fin    = Carbon::parse($finishedAt);



        $this->orderWorkRepository->updateById($id, [
            'finished_at'         => $fin,
            'installation_notes'  => $notes,
            'usuario_finalizacion'=> $userId,
            'fechafinalizacion'   => $fin,
            'status'              => 'completed',
        ]);
    }

    // función para marcar una orden de trabajo como en proceso
    public function registrarJornadas(
        int $ordenTrabajoId,
        array $jornadas,
        int $userId
    ): void
    {
        DB::transaction(function () use ($ordenTrabajoId, $jornadas, $userId) {

            foreach ($jornadas as $jornada) {

                $inicio = Carbon::parse($jornada['fecha'].' '.$jornada['hora_inicio']);
                $fin    = Carbon::parse($jornada['fecha'].' '.$jornada['hora_fin']);

                if ($fin->lte($inicio)) {
                    throw new \Exception('La hora final debe ser mayor a la hora inicial.');
                }

                $this->orderWorkRepository->crearJornada([
                    'orden_trabajo_id' => $ordenTrabajoId,
                    'acompanante_ot' => isset($jornada['instaladores']) && count($jornada['instaladores']) ? json_encode($jornada['instaladores']) : null,
                    'fecha' => $jornada['fecha'],
                    'hora_inicio' => $jornada['hora_inicio'],
                    'hora_fin' => $jornada['hora_fin'],
                    'horas_trabajadas' => round($inicio->diffInMinutes($fin) / 60, 2),
                    'observaciones' => $jornada['observaciones'] ?? null,
                    'user_otj' => $userId,
                ]);
            }
        });
    }


    // función para obtener el material de una orden de trabajo por ID
    public function getPedidoHgiPorOT(int $workOrderId)
    {
        return $this->orderWorkRepository->getPedidoHgiPorOT($workOrderId);
    }
}