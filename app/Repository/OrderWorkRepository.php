<?php

namespace App\Repository;

use App\Models\OrdenTrabajoModel;
use App\Models\MaterialModel;
use App\Models\InstaladorModel;
use App\Models\OrderWorkModel;
use App\Models\WorkOrdersMaterialsModel;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderWorkRepository
{
    protected OrderWorkModel $orderWorkModel;
    protected WorkOrdersMaterialsModel $workOrdersMaterialsModel;
    protected OrdenTrabajoModel $ordenTrabajoModel;

    public function __construct(OrderWorkModel $orderWorkModel, WorkOrdersMaterialsModel $workOrdersMaterialsModel, OrdenTrabajoModel $ordenTrabajoModel)
    {
        $this->orderWorkModel = $orderWorkModel;
        $this->workOrdersMaterialsModel = $workOrdersMaterialsModel;
        $this->ordenTrabajoModel = $ordenTrabajoModel;
    }

    // Métodos para manejar la lógica de negocio relacionada con órdenes de trabajo
    public function getAllOrders()
    {
        return $this->orderWorkModel->paginate(15);
    }

    /* funcion para crear una nueva órden de trabajo */
    public function createOrderWork(array $data)
    {
        return $this->orderWorkModel->create($data);
    }


    // función para obtener las órdenes de trabajo asignadas
    public function findById(int $id): OrderWorkModel
    {
        return OrderWorkModel::findOrFail($id);
    }


    // función para guardar una órden de trabajo
    public function save(OrderWorkModel $orderWork): bool
    {
        return $orderWork->save();
    }

    
    /*  funcion para obtener las órdenes de trabajo asignadas  */
    public function getOrderAsignados($vendorId = null)
    {
        $perfil = Auth::user()->perfil_usuario_id;

        // ADMIN (1,2) → Ver todo SIN restricciones
        if (in_array($perfil, [1, 2], true)) {
            return $this->orderWorkModel->with('instalador', 'pedidosMateriales')
            ->withCount('pedidosMateriales')
            ->orderBy('status', 'desc')
            ->paginate(15);
        }

        // INSTALADOR → traer solo sus órdenes
        if ($perfil === 7) {
            return $this->orderWorkModel
                ->with('instalador', 'pedidosMateriales')
                ->withCount('pedidosMateriales')
                ->orderBy('status', 'desc')
                ->paginate(15);
        }

        // =========================
        // ASESOR → SUS OT (userreg_ot)
        // =========================
        if ($perfil === 5) {
            if (empty($vendorId)) {
                return $this->orderWorkModel->whereRaw('1 = 0')->paginate(15);
            }

            return $this->orderWorkModel->with('instalador', 'pedidosMateriales')->withCount('pedidosMateriales')->where('usereg_ot', $vendorId)->orderBy('status', 'desc')->paginate(15);
        }

        // Otros perfiles → ver todo
        return $this->orderWorkModel->with('instalador')
        ->orderBy('status', 'desc')
        ->paginate(15);
    }

    public function getPedidoHgiPorOT(int $pedidoId)
    {
        $ot = \App\Models\OrderWorkModel::findOrFail($pedidoId);

        $documentos = [];

        // 🔹 PD GLOBAL
        if ($ot->pedido) {
            $documentos[] = $ot->pedido;
        }

        // 🔹 PD SERVICIO (si existe)
        if ($ot->pd_servicio && $ot->pd_servicio != $ot->pedido) {
            $documentos[] = $ot->pd_servicio;
        }

        return DB::connection('sqlsrv')
            ->table('TblDetalleDocumentos as d')
            ->join('TblProductos as p', 'p.StrIdProducto', '=', 'd.StrProducto')
            ->join('TblDocumentos as t', 't.IntDocumento', '=', 'd.IntDocumento')
            ->join('TblTerceros as tc', 'tc.StrIdTercero', '=', 't.StrTercero')
            ->whereIn('d.IntDocumento', $documentos)
            ->where('d.IntTransaccion', 109)
            ->select([
                'd.IntDocumento as pedido',
                'tc.StrNombre as cliente',

                'p.StrLinea as linea',
                'p.StrIdProducto as codigo_producto',
                'p.StrDescripcion as producto',

                DB::raw('CAST(d.IntCantidad AS DECIMAL(18,2)) as cantidad'),
                DB::raw('CAST(d.IntValorUnitario AS DECIMAL(18,2)) as valor_unitario'),
                DB::raw('CAST(ISNULL(d.IntValorDescuento,0) AS DECIMAL(18,2)) as valor_descuento'),

                DB::raw('CAST(d.IntCantidad * d.IntValorUnitario AS DECIMAL(18,2)) as subtotal'),

                DB::raw('CAST(
                    (d.IntCantidad * d.IntValorUnitario)
                    - ISNULL(d.IntValorDescuento,0)
                AS DECIMAL(18,2)) as total_con_descuento'),
            ])
            ->orderBy('d.IntDocumento')
            ->orderBy('p.StrLinea')
            ->get();
    }

    // función para obtener el detalle de una orden de trabajo por número de documento
    public function getMaterialsByOrderId($orderId)
    {
        return DB::table('work_orders_materials as wom')
       // ->join('materiales as m', 'm.id_material', '=', 'wom.material_id')
        ->where('wom.work_order_id', $orderId)
        ->select('wom.id_work_order_material', 'wom.material_id as id_material', 'wom.material_id', 'wom.cantidad', 'wom.ultimo_costo')
        ->orderBy('wom.material_id')->get();
    }

    // función para buscar materiales por nombre o código
    public function getMaterialsByMaterialName($materialName)
    {
        $sql = "
            SELECT 
                p.StrIdProducto AS codigo,
                p.StrDescripcion AS nombre,
                p.StrParam1 AS ubicacion,

                ISNULL((
                    SELECT SUM(s.IntSaldoI + s.IntEntradas - s.IntSalidas - s.IntSalidasT)
                    FROM QrySaldosInv1 s
                    WHERE s.StrProducto = p.StrIdProducto
                    AND s.IntBodega = '01'
                    AND s.IntAno = YEAR(GETDATE())
                    AND s.IntPeriodo = MONTH(GETDATE())
                    AND s.IntEmpresa = '01'
                ),0) AS saldo_inventario,

                ISNULL((
                    SELECT SUM(CASE 
                            WHEN sp.IntSaldoFinal > 0 THEN sp.IntSaldoFinal 
                            ELSE 0
                        END)
                    FROM QrySaldoPedidos sp
                    WHERE sp.StrProducto = p.StrIdProducto
                    AND sp.IntTransaccion = 109
                    AND sp.IntPeriodo = MONTH(GETDATE())
                    AND sp.IntAno = YEAR(GETDATE())
                ),0) AS saldo_reservado,

                -- AQUI ESTA LO QUE NECESITAS
                (
                    ISNULL((
                        SELECT SUM(s.IntSaldoI + s.IntEntradas - s.IntSalidas - s.IntSalidasT)
                        FROM QrySaldosInv1 s
                        WHERE s.StrProducto = p.StrIdProducto
                        AND s.IntBodega = '01'
                        AND s.IntAno = YEAR(GETDATE())
                        AND s.IntPeriodo = MONTH(GETDATE())
                        AND s.IntEmpresa = '01'
                    ),0)
                    -
                    ISNULL((
                        SELECT SUM(CASE 
                                WHEN sp.IntSaldoFinal > 0 THEN sp.IntSaldoFinal 
                                ELSE 0
                            END)
                        FROM QrySaldoPedidos sp
                        WHERE sp.StrProducto = p.StrIdProducto
                        AND sp.IntTransaccion = 109
                        AND sp.IntPeriodo = MONTH(GETDATE())
                        AND sp.IntAno = YEAR(GETDATE())
                    ),0)
                ) AS saldo_disponible

            FROM TblProductos p
            WHERE p.StrIdProducto LIKE ?
        ";

        return DB::connection('sqlsrv')->select($sql, [
            "%$materialName%",
            "%$materialName%"
        ]);
    }



    // Función para obtener el costo actual de un producto
    public function getCostoActualProducto(string  $codigoProducto)
    {
        $sql = "
            SELECT TOP 1
                SUM(IntValorFinal) as costo_unitario
            FROM QrySaldosInvCosto3
            WHERE IntBodega = '01'
            AND IntAno = YEAR(GETDATE())
            AND IntPeriodo = MONTH(GETDATE())
            AND StrProducto = ?
            GROUP BY IntEmpresa, IntAno, IntPeriodo, StrProducto, IntBodega
        ";

        $result = DB::connection('sqlsrv')->select($sql, [$codigoProducto]);

        return $result[0]->costo_unitario ?? 0;
    }


    // función para obtener el producto por código
    public function getProductoByCodigo($codigo){
        
    }

    // función para buscar materiales por nombre o código
    public function findOrFail(int $id): OrderWorkModel
    {
        return $this->orderWorkModel->findOrFail($id);
    }

    // función para actualizar una orden de trabajo por ID
    public function updateById(int $id, array $data): bool
    {
        return $this->orderWorkModel->where('id_work_order', $id)->update($data);
    }

    // función para eliminar una orden de trabajo por ID
    public function crearJornada(array $data)
    {
        return $this->ordenTrabajoModel->create([
            'orden_trabajo_id'  => $data['orden_trabajo_id'],
            'numero_jornada'    => $data['numero_jornada'], // 👈 obligatorio
            'fecha'             => $data['fecha'],
            'hora_inicio'       => $data['hora_inicio'],
            'hora_fin'          => $data['hora_fin'],
            'horas_trabajadas'  => $data['horas_trabajadas'],
            'observaciones'     => $data['observaciones'],
            'acompanante_ot'    => $data['acompanante_ot'],
            'fechareg_otj'      => now(),
            'user_otj'          => $data['user_otj'],
        ]);
    }


    // función para obtener una orden de trabajo con sus relaciones
    public function findWithRelations(int $id): OrderWorkModel
    {
        return OrderWorkModel::with([
            'instalador',
            'pedidosMateriales.instalador',
            'pedidosMateriales.items',
            'UsuariosOT'
        ])->findOrFail($id);
    }

    // función para verificar si una orden de trabajo está finalizada
    public function isCompleted(): bool
    {
        return $this->orderWorkModel->status === 'completed';
    }


    // función para obtener los materiales de una orden de trabajo por ID
    public function getManoObra(int $id)
    {
        return DB::table('vw_calculo_mano_obra_ot')
            ->where('id_work_order', $id)
            ->get();
    }


    // función para obtener los materiales de una orden de trabajo
    public function getMateriales(int $id)
    {
        return DB::table('work_orders_materials')
            ->where('work_order_id', $id)
            ->get();
    }


    // función para obtener los servicios de una orden de trabajo
    public function getServicios(int $pdServicio)
    {
        return DB::connection('sqlsrv')
            ->table('TblDetalleDocumentos as d')
            ->join('TblProductos as p', 'p.StrIdProducto', '=', 'd.StrProducto')
            ->where('d.IntDocumento', $pdServicio)
            ->where('d.IntTransaccion', 109)
            ->where('p.StrLinea', 40)
            ->selectRaw("
                p.StrIdProducto as codigo,
                p.StrDescripcion as descripcion,
                d.IntCantidad as cantidad,
                d.IntValorUnitario as valor_unitario,
                (d.IntCantidad * d.IntValorUnitario) - ISNULL(d.IntValorDescuento,0) as total
            ")
            ->get();
    }
}