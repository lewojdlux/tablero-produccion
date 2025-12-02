<?php


namespace App\Repository;

use App\Models\ProductionOrder;
use Illuminate\Support\Facades\Auth;

class ProductionOrderRepository
{

    protected $modelProductionOrder;



    // constructor
    public function __construct(ProductionOrder $modelProductionOrder)
    {
        $this->modelProductionOrder = $modelProductionOrder;
    }


    // list all production orders
    public function listOrders( array $filters = [] ): array
    {
        return $this->modelProductionOrder::all()->toArray();
    }

    /* Traer las primeras 200 ordenes de producción */
    public function getOrdersForTable(array $f = []): array
    {
            $q = $this->modelProductionOrder::query()
            ->select([
                'id_production_order',
                'ticket_code',
                'tipo_transaccion',
                'n_documento',
                'pedido',
                'tercero',
                'luminaria',
                'vendedor',
                'vendedor_username',
                'status',
                'updated_at',
                'started_at',
                'paused_at',
                'paused_accumulated_min',
                'finished_at',
                'approved_at'
            ]);

            // 🔒 Si es ASESOR (perfil 5), solo sus órdenes
            $perfil = (int) (Auth::user()->perfil_usuario_id ?? 0);
            if ($perfil === 5) {
                $asesorCode = Auth::user()->identificador_asesor; // ← tu campo en users
                $q->where('vendedor_username', $asesorCode);
            }

            // 🔎 Buscador global
            if (!empty($f['q'])) {
                $term = trim($f['q']);
                $q->where(function ($qq) use ($term) {
                    $qq->where('ticket_code', 'like', "%{$term}%")
                    ->orWhere('pedido', 'like', "%{$term}%")
                    ->orWhere('luminaria', 'like', "%{$term}%")
                    ->orWhere('vendedor', 'like', "%{$term}%");
                });
            }

            // Filtros puntuales
            if (!empty($f['pedido']))    $q->where('pedido', 'like', '%'.$f['pedido'].'%');
            if (!empty($f['asesor']))    $q->where('vendedor', 'like', '%'.$f['asesor'].'%');
            if (!empty($f['luminaria'])) $q->where('luminaria', 'like', '%'.$f['luminaria'].'%');
            if (!empty($f['status']))    $q->where('status', $f['status']);


             // Solo permitir filtro por asesor si NO es perfil 5 (admin/super)
            if ($perfil !== 5 && !empty($f['asesor'])) {
                // admite código exacto o nombre
                $q->where(function($qq) use ($f) {
                    $qq->where('vendedor_username', $f['asesor'])
                    ->orWhere('vendedor', 'like', '%'.$f['asesor'].'%');
                });
            }



            if (!empty($f['start'])) $q->whereDate('created_at', '>=', $f['start']);
            if (!empty($f['end']))   $q->whereDate('updated_at', '<=', $f['end']);

            $q->orderByRaw("
                CASE status
                    WHEN 'queued'      THEN 1
                    WHEN 'in_progress' THEN 2
                    WHEN 'done'        THEN 3
                    WHEN 'approved'    THEN 4
                    ELSE 5
                END ASC
            ")->orderByRaw("
                CASE
                    WHEN status = 'queued'      THEN COALESCE(queued_at, created_at)
                    WHEN status = 'in_progress' THEN COALESCE(started_at, updated_at)
                    WHEN status = 'done'        THEN COALESCE(finished_at, updated_at)
                    WHEN status = 'approved'    THEN COALESCE(approved_at, updated_at)
                    ELSE COALESCE(updated_at, created_at)
                END ASC
            ")->orderByDesc('id_production_order');

            return $q->get()->toArray();
    }


    /*  traer ordenes de produccion con el campo ID - PD - Transacción - Referencia - Vendedor */
    public function getOrdersBasicInfo(): array
    {
        return $this->modelProductionOrder::select('id_production_order', 'tipo_transaccion', 'n_documento', 'pedido', 'vendedor')
            ->get()
            ->toArray();
    }


}