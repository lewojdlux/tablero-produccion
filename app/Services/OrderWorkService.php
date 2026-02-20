<?php


namespace App\Services;

use Exception;

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

    public function getCostoActualProducto(string $workOrderId)
    {
        return $this->orderWorkRepository->getCostoActualProducto($workOrderId);
    }

    // función para iniciar una orden de trabajo
    public function iniciarOrdenTrabajo(int $id): void
    {
        $workOrder = $this->orderWorkRepository->findById($id);

        //  Validación 1: Debe tener instalador principal
        if (empty($workOrder->instalador_id)) {
            throw new Exception(
                'Debe asignar un instalador principal antes de iniciar la OT.',
                422
            );
        }

        //  Validación 2: No debe estar ya iniciada
        if ($workOrder->status === 'in_progress') {
            throw new Exception(
                'La orden ya está iniciada.',
                422
            );
        }

        //  Validación 3: No debe estar finalizada
        if ($workOrder->status === 'completed') {
            throw new Exception(
                'No puede iniciar una orden finalizada.',
                422
            );
        }

        $workOrder->status = 'in_progress';

        $this->orderWorkRepository->save($workOrder);
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

            //  Obtener último número de jornada
            $ultima = DB::table('orden_trabajo_jornadas')
                ->where('orden_trabajo_id', $ordenTrabajoId)
                ->max('numero_jornada');

            $consecutivoBase = $ultima ?? 0;

            foreach ($jornadas as $index => $jornada) {

                $inicio = Carbon::parse($jornada['fecha'].' '.$jornada['hora_inicio']);
                $fin    = Carbon::parse($jornada['fecha'].' '.$jornada['hora_fin']);

                if ($fin->lte($inicio)) {
                    throw new \Exception('La hora final debe ser mayor a la hora inicial.');
                }

                //  AQUÍ VA LA VALIDACIÓN DE DUPLICADO
                $existe = DB::table('orden_trabajo_jornadas')
                    ->where('orden_trabajo_id', $ordenTrabajoId)
                    ->whereDate('fecha', $jornada['fecha'])
                    ->exists();

                if ($existe) {
                    throw new \Exception('Ya existe una jornada registrada para esta fecha.');
                }

                //  Calcular número de jornada
                $numeroJornada = $consecutivoBase + $index + 1;

                $instaladores = $jornada['instaladores'] ?? [];

                if (is_string($instaladores)) {
                    $instaladores = json_decode($instaladores, true);
                }

                $this->orderWorkRepository->crearJornada([
                    'orden_trabajo_id' => $ordenTrabajoId,
                    'numero_jornada'   => $numeroJornada, // 
                    'acompanante_ot'   =>  !empty($instaladores) ? $instaladores : null,
                    'fecha'            => $jornada['fecha'],
                    'hora_inicio'      => $jornada['hora_inicio'],
                    'hora_fin'         => $jornada['hora_fin'],
                    'horas_trabajadas' => round($inicio->diffInMinutes($fin) / 60, 2),
                    'observaciones'    => $jornada['observaciones'] ?? null,
                    'user_otj'         => $userId,
                ]);
            }
        });
    }


    // función para obtener el material de una orden de trabajo por ID
    public function getPedidoHgiPorOT(int $workOrderId)
    {
        return $this->orderWorkRepository->getPedidoHgiPorOT($workOrderId);
    }

    /*  función para asignar instaladores a una orden de trabajo */
    public function asignarInstaladores(
        int $workOrderId,
        int $principal,
        array $acompanantes = []
    ): void {

        $ot = $this->orderWorkRepository->findById($workOrderId);

        // 🔹 Normalizar acompañantes
        $acompanantesLimpios = collect($acompanantes)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id !== $principal)
            ->unique()
            ->values()
            ->toArray();

        DB::transaction(function () use ($ot, $principal, $acompanantesLimpios) {

            // Guardar principal
            $ot->instalador_id = $principal;
            $ot->save();

            // Sincronizar acompañantes
            $ot->acompanantes()->sync($acompanantesLimpios);

        });
    }


    // función para obtener el resumen final de una orden de trabajo
    public function obtenerResumenFinal(int $id): array
    {
        $ordenTrabajo = $this->orderWorkRepository->findWithRelations($id);

        if ($ordenTrabajo->status !== 'completed') {
            throw new Exception('La orden no está finalizada.');
        }

        $manoObra = $this->orderWorkRepository->getManoObra($id);
        $materiales = $this->orderWorkRepository->getMateriales($id);
        $servicios = $this->orderWorkRepository->getServicios($ordenTrabajo->pd_servicio);

        //  LÓGICA DE NEGOCIO AQUÍ
        $manoObraTotal = (float) $manoObra->sum('total');
        $solicitudTotal = (float) $materiales->sum('ultimo_costo');
        $pedidoTotal = (float) $servicios->sum('total');

        $utilidad = $pedidoTotal - $manoObraTotal - $solicitudTotal;

        $porcentajeUtilidad = $pedidoTotal > 0
            ? round(($utilidad / $pedidoTotal) * 100, 2)
            : 0;

        return compact(
            'ordenTrabajo',
            'manoObra',
            'manoObraTotal',
            'materiales',
            'solicitudTotal',
            'servicios',
            'pedidoTotal',
            'utilidad',
            'porcentajeUtilidad'
        );
    }
}