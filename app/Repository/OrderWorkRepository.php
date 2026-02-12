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

    /*  funcion para obtener las órdenes de trabajo asignadas  */
    public function getOrderAsignados($vendorId = null)
    {
        $perfil = Auth::user()->perfil_usuario_id;

        // ADMIN (1,2) → Ver todo SIN restricciones
        if (in_array($perfil, [1, 2], true)) {
            return $this->orderWorkModel->with('instalador', 'pedidosMateriales')->withCount('pedidosMateriales')->orderBy('status', 'desc')->paginate(15);
        }

        // INSTALADOR → traer solo sus órdenes
        if (in_array($perfil, [7], true)) {
            $identificador = Auth::user()->identificador_instalador;
            $instalador = InstaladorModel::where('identificador_usuario', $identificador)->first();

            // Si no tiene instalador, devolver vacío PERO SIN ROMPER LA VISTA
            if (!$instalador) {
                return $this->orderWorkModel
                    ->whereRaw('1 = 0') // colección vacía, segura
                    ->paginate(15);
            }

            return $this->orderWorkModel->with('instalador', 'pedidosMateriales')->where('instalador_id', $instalador->id_instalador)->orderBy('status', 'desc')->paginate(15);
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
        return $this->orderWorkModel->with('instalador')->orderBy('status', 'desc')->paginate(15);
    }

    public function getPedidoHgiPorOT(int $pedidoId)
    {
        return DB::connection('sqlsrv')
            ->table('TblDetalleDocumentos as d')
            ->join('TblProductos as p', 'p.StrIdProducto', '=', 'd.StrProducto')
            ->where('d.IntDocumento', $pedidoId)
            ->where('d.IntTransaccion', 109)
            ->where('p.StrLinea', 40)
            ->select([
                'd.IntDocumento as pedido',

                DB::raw('(
                SELECT TOP 1 tc.StrNombre
                FROM TblDocumentos td
                INNER JOIN TblTerceros tc
                    ON tc.StrIdTercero = td.StrTercero
                WHERE td.IntDocumento = d.IntDocumento
            ) as cliente'),

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
            ->get();
    }

    // función para obtener el detalle de una orden de trabajo por número de documento
    public function getMaterialsByOrderId($orderId)
    {
        return DB::table('work_orders_materials as wom')->join('materiales as m', 'm.id_material', '=', 'wom.material_id')->where('wom.work_order_id', $orderId)->select('wom.id_work_order_material', 'wom.material_id as id_material', 'm.codigo_material', 'm.nombre_material', 'wom.cantidad')->orderBy('m.nombre_material')->get();
    }

    // función para buscar materiales por nombre o código
    public function getMaterialsByMaterialName($materialName)
    {
        return MaterialModel::where('nombre_material', 'like', '%' . $materialName . '%')
            ->orWhere('codigo_material', 'like', '%' . $materialName . '%')
            ->get();
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
        return $this->ordenTrabajoModel->updateOrCreate(
            [
                'orden_trabajo_id' => $data['orden_trabajo_id'],
                'fecha' => $data['fecha'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
            ],
            [
                'horas_trabajadas' => $data['horas_trabajadas'],
                'observaciones' => $data['observaciones'],
                'acompanante_ot' => $data['acompanante_ot'],
                'fechareg_otj' => now(),
                'user_otj' => $data['user_otj'],
            ],
        );
    }
}