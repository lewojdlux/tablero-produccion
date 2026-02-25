<?php

namespace App\Services;

use App\Models\OrderWorkModel;
use Exception;

use App\Repository\OrderWorkRepository;
use App\Repository\ProductionRepository;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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

    // función para traer los PD de HGI
    public function getOrdenesTrabajo($search = null, $vendorId = null)
    {
        // Lógica para crear una nueva orden de trabajo
        return $this->productionRepository->getOrdenesTrabajo($search, $vendorId);
    }

    // función para crear una nueva orden de trabajo
    public function createOrderWork(array $data)
    {
        // Validar PD
        /*$pdValido = $this->orderWorkRepository->getPedidoHgiExistente([
            'pd_servicio' => $data['pd_servicio'] ?? null,
            'vendedor_username' => $data['vendedor_username'] ?? null,
            'tercero_id' => $data['tercero_id'] ?? null,
        ]);

        if (!$pdValido) {
            throw new \Exception('El PD de servicio no corresponde al cliente o asesor.');
        }*/

        // 🛡 Validar que no exista OT
        if ($this->orderWorkRepository->existePorDocumento($data['n_documento'])) {
            throw new \Exception('La orden ya está registrada.');
        }

        return $this->orderWorkRepository->createOrderWork([
            'n_documento' => $data['n_documento'],
            'pedido' => $data['n_documento'],
            'tercero' => $data['tercero'],
            'vendedor' => $data['vendedor'],
            'codigo_asesor' => $data['vendedor_username'],
            'instalador_id' => null,
            'pd_servicio' => $data['pd_servicio'] ?? null,
            'periodo' => $data['periodo'],
            'ano' => $data['ano'],
            'estado_factura' => $data['status'],
            'n_factura' => $data['n_factura'],
            'status' => $data['status'],
            'description' => $data['obsv_pedido'],
            'usereg_ot' => Auth::user()->id,
        ]);
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

    public function programarOT(array $data)
    {
        $ot = OrderWorkModel::findOrFail($data['id_work_order']);

        if ($ot->status === 'completed') {
            throw new \Exception('No se puede programar una OT finalizada.');
        }

        $ot->fecha_programada = $data['fecha_programada'];
        $ot->fecha_programada_fin = $data['fecha_programada_fin'];
        $ot->observacion_programacion = $data['observacion_programacion'];
        $ot->save();

        return $ot;
    }

    // función para iniciar una orden de trabajo
    public function iniciarOrdenTrabajo(int $id): void
    {
        $workOrder = $this->orderWorkRepository->findById($id);

        //  Validación 1: Debe tener instalador principal
        if (empty($workOrder->instalador_id)) {
            throw new Exception('Debe asignar un instalador principal antes de iniciar la OT.', 422);
        }

        //  Validación 2: No debe estar ya iniciada
        if ($workOrder->status === 'in_progress') {
            throw new Exception('La orden ya está iniciada.', 422);
        }

        //  Validación 3: No debe estar finalizada
        if ($workOrder->status === 'completed') {
            throw new Exception('No puede iniciar una orden finalizada.', 422);
        }

        $workOrder->status = 'in_progress';

        $this->orderWorkRepository->save($workOrder);
    }

    // función para finalizar una orden de trabajo
    public function finalizarOT(int $id, string $finishedAt, string $notes, int $userId): void
    {
        $ot = $this->orderWorkRepository->findOrFail($id);

        if ($ot->status !== 'in_progress') {
            throw new \Exception('La orden de trabajo no está en ejecución');
        }

        $fin = Carbon::parse($finishedAt);

        $this->orderWorkRepository->updateById($id, [
            'finished_at' => $fin,
            'installation_notes' => $notes,
            'usuario_finalizacion' => $userId,
            'fechafinalizacion' => $fin,
            'status' => 'completed',
        ]);
    }

    // función para marcar una orden de trabajo como en proceso
    public function registrarJornadas(int $ordenTrabajoId, array $jornadas, int $userId): void
    {
        DB::transaction(function () use ($ordenTrabajoId, $jornadas, $userId) {
            $ot = DB::table('work_orders')->where('id_work_order', $ordenTrabajoId)->first();

            if (!$ot) {
                throw new \Exception('Orden de trabajo no encontrada.');
            }

            if (!$ot->fecha_programada || !$ot->fecha_programada_fin) {
                throw new \Exception('La orden no tiene fechas programadas.');
            }

            $consecutivoBase = DB::table('orden_trabajo_jornadas')->where('orden_trabajo_id', $ordenTrabajoId)->count();

            foreach ($jornadas as $index => $jornada) {
                // VALIDAR RANGO FECHAS
                if ($jornada['fecha'] < $ot->fecha_programada) {
                    throw new \Exception('La fecha es menor a la fecha programada.');
                }

                if ($jornada['fecha'] > $ot->fecha_programada_fin) {
                    throw new \Exception('La fecha supera la fecha final programada.');
                }

                // VALIDAR DUPLICADO POR FECHA
                $existe = DB::table('orden_trabajo_jornadas')->where('orden_trabajo_id', $ordenTrabajoId)->whereDate('fecha', $jornada['fecha'])->exists();

                if ($existe) {
                    throw new \Exception('Ya existe una jornada registrada para esta fecha.');
                }

                $inicio = Carbon::parse($jornada['fecha'] . ' ' . $jornada['hora_inicio']);

                $fin = null;
                $horasTrabajadas = null;

                if (!empty($jornada['hora_fin'])) {
                    $fin = Carbon::parse($jornada['fecha'] . ' ' . $jornada['hora_fin']);

                    if ($fin->lte($inicio)) {
                        throw new \Exception('La hora final debe ser mayor a la hora inicial.');
                    }

                    $horasTrabajadas = round($inicio->diffInMinutes($fin) / 60, 2);
                }

                $numeroJornada = $consecutivoBase + $index + 1;

                $instaladores = $jornada['instaladores'] ?? [];

                if (is_string($instaladores)) {
                    $instaladores = json_decode($instaladores, true);
                }

                $this->orderWorkRepository->crearJornada([
                    'orden_trabajo_id' => $ordenTrabajoId,
                    'numero_jornada' => $numeroJornada,
                    'acompanante_ot' => !empty($instaladores) ? $instaladores : null,
                    'fecha' => $jornada['fecha'],
                    'hora_inicio' => $jornada['hora_inicio'],
                    'hora_fin' => $jornada['hora_fin'] ?? null,
                    'horas_trabajadas' => $horasTrabajadas,
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

    /*  función para asignar instaladores a una orden de trabajo */
    public function asignarInstaladores(int $workOrderId, int $principal, array $acompanantes = []): void
    {
        $ot = $this->orderWorkRepository->findById($workOrderId);

        // 🔹 Normalizar acompañantes
        $acompanantesLimpios = collect($acompanantes)->map(fn($id) => (int) $id)->filter(fn($id) => $id !== $principal)->unique()->values()->toArray();

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

        $calculoService = app(CalculoManoObraService::class);

        // =====================
        // TRAER ACOMPAÑANTES
        // =====================
        $acompanantes = DB::table('orden_trabajo_jornadas as otj')
            ->join('instalador as ia', function ($join) {
                $join->whereRaw("
                JSON_CONTAINS(
                    otj.acompanante_ot,
                    ia.id_instalador
                )
            ");
            })
            ->where('otj.orden_trabajo_id', $id)
            ->select('otj.fecha', 'otj.hora_inicio', 'otj.hora_fin', 'ia.id_instalador', 'ia.nombre_instalador', 'ia.valor_hora')
            ->get();

        $detalle = collect();

        // =====================
        // CALCULAR POR INSTALADOR
        // =====================
        foreach ($acompanantes as $r) {
            $calculo = $calculoService->calcularPagoJornada($r->fecha, $r->hora_inicio, $r->hora_fin, $r->valor_hora);

            foreach ($calculo as $c) {
                if ($c['horas'] > 0) {
                    $detalle->push([
                        'id_instalador' => $r->id_instalador,
                        'nombre_instalador' => $r->nombre_instalador,
                        'tipo' => $c['tipo'],
                        'horas' => $c['horas'],
                        'valor_hora' => $c['valor_hora'],
                        'total' => $c['total'],
                    ]);
                }
            }
        }

        // =====================
        // AGRUPAR POR INSTALADOR
        // =====================
        $manoObra = $detalle
            ->groupBy(function ($item) {
                return $item['id_instalador'] . '_' . $item['tipo'];
            })
            ->map(function ($items) {
                return (object) [
                    'id_instalador' => $items->first()['id_instalador'],
                    'nombre_instalador' => $items->first()['nombre_instalador'],
                    'tipo' => $items->first()['tipo'],
                    'horas' => round($items->sum('horas'), 2),
                    'valor_hora' => $items->first()['valor_hora'],
                    'total' => round($items->sum('total'), 2),
                ];
            })
            ->sortBy(function ($item) {
                $orden = [
                    'Ordinaria' => 1,
                    'Extra Diurna' => 2,
                    'Extra Nocturna' => 3,
                    'Dom/Fest Diurna' => 4,
                    'Dom/Fest Nocturna' => 5,
                ];

                return $orden[$item->tipo] ?? 99;
            })
            ->values()
            ->map(fn($item) => (object) $item);

        $manoObraTotal = $manoObra->sum('total') ?? 0;
        $materiales = $this->orderWorkRepository->getMateriales($id)->map(function ($m) {
            $cantidad = (float) ($m->cantidad ?? 0);
            $costo = (float) ($m->ultimo_costo ?? 0);

            $m->cantidad = $cantidad;
            $m->ultimo_costo = $costo;
            $m->total = round($cantidad * $costo, 2);

            return $m;
        });

        $solicitudTotal = $materiales->sum('total') ?? 0;

        $servicios = $this->orderWorkRepository->getServicios($ordenTrabajo->pd_servicio);

        $pedidoTotal = $servicios->sum('total') ?? 0;

        $utilidad = $pedidoTotal - $manoObraTotal - $solicitudTotal;

        $porcentajeUtilidad = $pedidoTotal > 0 ? round(($utilidad / $pedidoTotal) * 100, 2) : 0;

        return compact('ordenTrabajo', 'manoObra', 'manoObraTotal', 'materiales', 'solicitudTotal', 'servicios', 'pedidoTotal', 'utilidad', 'porcentajeUtilidad');
    }

    // función para obtener el PD de servicio existente
    public function getPedidoHgiExistente(array $data)
    {
        return $this->orderWorkRepository->getPedidoHgiExistente($data);
    }
}