<?php

namespace App\Repository;

use App\Models\OrdenTrabajoModel;
use App\Models\OrdenTrabajoNovedad;
use App\Models\OrderWorkFotoModel;
use App\Models\OrderWorkModel;
use App\Models\WorkOrdersMaterialsModel;
use Illuminate\Database\QueryException;
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

    // / registrar pd adicional
    public function anexarPdAdicional(array $data, $usuario)
    {
        // Traer OT completa
        $orden = DB::table('work_orders')
            ->where('id_work_order', $data['work_order_id'])
            ->first();

        if (! $orden) {
            throw new \Exception('Orden no encontrada.');
        }

        $pdValido = DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->where('t.IntDocumento', $data['pd_agregado'])
            ->where('t.IntTransaccion', 109)
            // ->where('t.StrTercero', $orden->tercero)
            ->where('t.StrDVendedor', $orden->codigo_asesor)
            ->exists();

        if (! $pdValido) {
            throw new \Exception('El PD no pertenece al cliente o asesor de la OT.');
        }

        // Validar duplicado
        $existe = DB::table('work_order_pd_adicionales')
            ->where('work_order_id', $data['work_order_id'])
            ->where('pd_agregado', $data['pd_agregado'])
            ->exists();

        if ($existe) {
            throw new \Exception('Este PD ya fue anexado.');
        }

        // Insertar
        try {
            DB::table('work_order_pd_adicionales')->insert([
                'work_order_id' => $data['work_order_id'],
                'pd_agregado' => $data['pd_agregado'],
                'asesor_hgi_id' => $orden->codigo_asesor,
                'usuario_registra_id' => $usuario->id,
                'observacion' => $data['observacion'] ?? null,
                'fecha_registro' => now(),
            ]);

        } catch (QueryException $e) {

            // Error 1062 = duplicate entry (MySQL)
            if ($e->getCode() == 23000) {
                throw new \Exception('Este PD ya fue anexado a la orden.');
            }

            throw $e;
        }
    }

    // función para verificar si una orden de trabajo existe por # de documento
    public function existePorDocumento($documento)
    {
        return $this->orderWorkModel
            ->where('n_documento', $documento)
            ->exists();
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

    public function crearNovedad(array $data)
    {
        return OrdenTrabajoNovedad::create($data);
    }

    /*  funcion para obtener las órdenes de trabajo asignadas */
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

            return $this->orderWorkModel->with('instalador', 'pedidosMateriales')
                ->withCount('pedidosMateriales')
                ->where('codigo_asesor', Auth::user()->identificador_asesor)
                ->orderBy('status', 'desc')
                ->paginate(15);
        }

        // Otros perfiles → ver todo
        return $this->orderWorkModel->with('instalador')
            ->orderBy('status', 'desc')
            ->paginate(15);
    }

    // función para obtener el material de una orden de trabajo por ID
    public function getPedidoHgiPorOT(int $pedidoId)
    {
        $ot = \App\Models\OrderWorkModel::findOrFail($pedidoId);

        $documentos = [];

        //  PD GLOBAL
        if ($ot->pedido) {
            $documentos[] = $ot->pedido;
        }

        //  PD SERVICIO (si existe)
        if ($ot->pd_servicio && $ot->pd_servicio != $ot->pedido) {
            $documentos[] = $ot->pd_servicio;
        }

        //  PD ADICIONALES
        $pdAdicionales = DB::table('work_order_pd_adicionales')
            ->where('work_order_id', $pedidoId)
            ->pluck('pd_agregado')
            ->toArray();

        foreach ($pdAdicionales as $pd) {
            if (! in_array($pd, $documentos)) {
                $documentos[] = $pd;
            }
        }

        // SI NO HAY DOCUMENTOS
        if (empty($documentos)) {
            return [];
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
            img.Imagen,

            ISNULL(inv.saldo_inventario,0) AS saldo_inventario,
            ISNULL(res.saldo_reservado,0) AS saldo_reservado,

            ISNULL(inv.saldo_inventario,0) - ISNULL(res.saldo_reservado,0) AS saldo_disponible

            FROM TblProductos p

            LEFT JOIN TblImagenes img
                ON img.StrIdCodigo = p.StrIdProducto
                AND img.StrTabla = 'TblProductos'

            OUTER APPLY (
                SELECT
                    SUM(s.IntSaldoI + s.IntEntradas - s.IntSalidas - s.IntSalidasT) AS saldo_inventario
                FROM QrySaldosInv1 s
                WHERE s.StrProducto = p.StrIdProducto
                AND s.IntBodega = '01'
                AND s.IntAno = YEAR(GETDATE())
                AND s.IntPeriodo = MONTH(GETDATE())
                AND s.IntEmpresa = '01'
            ) inv

            OUTER APPLY (
                SELECT
                    SUM(CASE
                        WHEN sp.IntSaldoFinal > 0 THEN sp.IntSaldoFinal
                        ELSE 0
                    END) AS saldo_reservado
                FROM QrySaldoPedidos sp
                WHERE sp.StrProducto = p.StrIdProducto
                AND sp.IntTransaccion = 109
                AND sp.IntPeriodo = MONTH(GETDATE())
                AND sp.IntAno = YEAR(GETDATE())
            ) res

            WHERE
                p.StrIdProducto LIKE ?
                OR p.StrDescripcion LIKE ?
        ";

        return DB::connection('sqlsrv')->select($sql, [
            "%$materialName%",
            "%$materialName%",
        ]);
    }

    // función para obtener las órdenes de trabajo con filtros avanzados
    public function getAllMaterials($lastId, $limit)
    {
        $limit = (int) $limit;

        return DB::connection('sqlsrv')->select("
            SELECT TOP $limit
                p.StrIdProducto AS codigo,
                p.StrDescripcion AS nombre,
                p.StrParam1 AS ubicacion,
                img.Imagen,

                ISNULL(inv.saldo_inventario,0) AS saldo_inventario,
                ISNULL(res.saldo_reservado,0) AS saldo_reservado

            FROM TblProductos p WITH (NOLOCK)

            LEFT JOIN TblImagenes img WITH (NOLOCK)
                ON img.StrIdCodigo = p.StrIdProducto
                AND img.StrTabla = 'TblProductos'

            -- INVENTARIO PRECALCULADO
            LEFT JOIN (
                SELECT 
                    s.StrProducto,
                    SUM(s.IntSaldoI + s.IntEntradas - s.IntSalidas - s.IntSalidasT) AS saldo_inventario
                FROM QrySaldosInv1 s WITH (NOLOCK)
                WHERE s.IntBodega = '01'
                AND s.IntAno = YEAR(GETDATE())
                AND s.IntPeriodo = MONTH(GETDATE())
                AND s.IntEmpresa = '01'
                GROUP BY s.StrProducto
            ) inv ON inv.StrProducto = p.StrIdProducto

            -- RESERVAS PRECALCULADAS
            LEFT JOIN (
                SELECT 
                    sp.StrProducto,
                    SUM(CASE WHEN sp.IntSaldoFinal > 0 THEN sp.IntSaldoFinal ELSE 0 END) AS saldo_reservado
                FROM QrySaldoPedidos sp WITH (NOLOCK)
                WHERE sp.IntTransaccion = 109
                AND sp.IntPeriodo = MONTH(GETDATE())
                AND sp.IntAno = YEAR(GETDATE())
                GROUP BY sp.StrProducto
            ) res ON res.StrProducto = p.StrIdProducto

            WHERE p.StrIdProducto > ?
            ORDER BY p.StrIdProducto
        ", [$lastId]);
    }

    // Función para obtener el costo actual de un producto
    public function getCostoActualProducto(string $codigoProducto)
    {
        /*
            |--------------------------------------------------------------------------
            |  Buscar última compra histórica (Transacción 131)
            |--------------------------------------------------------------------------
        */

        $compra = DB::connection('sqlsrv')
            ->table('TblDetalleDocumentos as d')
            ->join('TblDocumentos as t', 't.IntDocumento', '=', 'd.IntDocumento')
            ->where('d.IntTransaccion', 131)
            ->where('d.StrProducto', $codigoProducto)
            ->orderByDesc('t.IntAno')
            ->orderByDesc('t.IntPeriodo')
            ->orderByDesc('t.IntDocumento') // último movimiento real
            ->select('d.IntValorUnitario')
            ->first();

        if ($compra && $compra->IntValorUnitario > 0) {
            return (float) $compra->IntValorUnitario;
        }

        /*
            |--------------------------------------------------------------------------
            |  Si nunca ha tenido compra → usar saldo actual
            |--------------------------------------------------------------------------
        */

        $saldo = DB::connection('sqlsrv')
            ->select("
                SELECT TOP 1
                    SUM(IntValorFinal) as costo_unitario
                FROM QrySaldosInvCosto3
                WHERE IntBodega = '01'
                AND IntAno = YEAR(GETDATE())
                AND IntPeriodo = MONTH(GETDATE())
                AND StrProducto = ?
                GROUP BY IntEmpresa, IntAno, IntPeriodo, StrProducto, IntBodega
            ", [$codigoProducto]);

        return (float) ($saldo[0]->costo_unitario ?? 0);
    }

    // función para obtener el producto por código
    public function getProductoByCodigo($codigo) {}

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
            'orden_trabajo_id' => $data['orden_trabajo_id'],
            'numero_jornada' => $data['numero_jornada'], // obligatorio
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'horas_trabajadas' => $data['horas_trabajadas'],
            'observaciones' => $data['observaciones'],
            'acompanante_ot' => $data['acompanante_ot'],
            'fechareg_otj' => now(),
            'user_otj' => $data['user_otj'],
        ]);
    }

    // función para obtener una orden de trabajo con sus relaciones
    public function findWithRelations(int $id): OrderWorkModel
    {
        return OrderWorkModel::with([
            'instalador',
            'pedidosMateriales.instalador',
            'pedidosMateriales.items',
            'UsuariosOT',
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
            ->selectRaw('
                p.StrIdProducto as codigo,
                p.StrDescripcion as descripcion,
                d.IntCantidad as cantidad,
                d.IntValorUnitario as valor_unitario,
                (d.IntCantidad * d.IntValorUnitario) - ISNULL(d.IntValorDescuento,0) as total
            ')
            ->get();
    }

    // función para obtener los servicios de una orden de trabajo por múltiples documentos
    public function getServiciosPorDocumentos(array $documentos)
    {
        if (empty($documentos)) {
            return collect();
        }

        return DB::connection('sqlsrv')
            ->table('TblDetalleDocumentos as d')
            ->join('TblProductos as p', 'p.StrIdProducto', '=', 'd.StrProducto')
            ->whereIn('d.IntDocumento', $documentos)
            ->where('d.IntTransaccion', 109)
            ->selectRaw('
                d.IntDocumento as pedido,
                p.StrLinea,
                p.StrIdProducto as codigo,
                p.StrDescripcion as descripcion,
                d.IntCantidad as cantidad,
                d.IntValorUnitario as valor_unitario,
                (d.IntCantidad * d.IntValorUnitario) - ISNULL(d.IntValorDescuento,0) as total
            ')
            ->get();
    }

    // función para obtener el PD de servicio existente
    public function getPedidoHgiExistente(array $data)
    {
        return DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblDetalleDocumentos as dd', 'dd.IntDocumento', '=', 't.IntDocumento')
            ->join('TblProductos as p', 'p.StrIdProducto', '=', 'dd.StrProducto')
            ->where('t.IntDocumento', $data['pd_servicio'])
            ->where('t.StrDVendedor', $data['vendedor_username'])
            ->where('t.StrTercero', $data['tercero_id'])
            ->where('p.StrLinea', '40')
            ->exists();

        if (! $pdValido) {
            throw new \Exception('El PD de servicio no corresponde al cliente o asesor.');
        }
    }

    // función para guardar las fotos de una orden de trabajo
    public function guardarFotos($orderId, $files)
    {
        if (! $files) {
            return false;
        }

        foreach ($files as $file) {

            $path = $file->store("ordenes_trabajo/$orderId", 'public');

            $tipo = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'imagen';

            OrderWorkFotoModel::create([
                'order_work_id' => $orderId,
                'ruta' => $path,
                'tipo' => $tipo,
            ]);
        }

        return true;

    }
}
