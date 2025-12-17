<?php

namespace App\Repository;


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

    public function __construct(OrderWorkModel $orderWorkModel, WorkOrdersMaterialsModel $workOrdersMaterialsModel)
    {
        $this->orderWorkModel = $orderWorkModel;
        $this->workOrdersMaterialsModel = $workOrdersMaterialsModel;
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
    public function getOrderAsignados()
    {
        $perfil = Auth::user()->perfil_usuario_id;

        // ADMIN (1,2) → Ver todo SIN restricciones
        if (in_array($perfil, [1, 2], true)) {
            return $this->orderWorkModel->with('instalador')->orderBy('status', 'desc')->paginate(15);
        }

        // INSTALADOR → traer solo sus órdenes
        if ($perfil == 7) {
            $identificador = Auth::user()->identificador_instalador;

            $instalador = InstaladorModel::where('identificador_usuario', $identificador)->first();

            // Si no tiene instalador, devolver vacío PERO SIN ROMPER LA VISTA
            if (!$instalador) {
                return $this->orderWorkModel
                    ->whereRaw('1 = 0') // colección vacía, segura
                    ->paginate(15);
            }

            return $this->orderWorkModel->with('instalador')->where('instalador_id', $instalador->id_instalador)->orderBy('status', 'desc')->paginate(15);
        }

        // Otros perfiles → ver todo
        return $this->orderWorkModel->with('instalador')->orderBy('status', 'desc')->paginate(15);
    }


    public function getMaterialsByOrderId($orderId)
    {
        return DB::table('work_orders_materials as wom')
        ->join('materiales as m', 'm.id_material', '=', 'wom.material_id')
        ->where('wom.work_order_id', $orderId)
        ->select(
            'wom.id_work_order_material',
            'wom.material_id as id_material',
            'm.nombre_material',
            'wom.cantidad'
        )
        ->orderBy('m.nombre_material')
        ->get();
    }

    public function getMaterialsByMaterialName($materialName)
    {
        return MaterialModel::where('nombre_material', 'like', '%' . $materialName . '%')->orWhere('codigo_material', 'like', '%' . $materialName . '%')
        ->get();
    }
}