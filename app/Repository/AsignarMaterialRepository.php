<?php

namespace App\Repository;

use App\Models\MaterialModel;
use App\Models\InstaladorModel;
use App\Models\OrderWorkModel;
use App\Models\WorkOrdersMaterialsModel;



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;






class AsignarMaterialRepository
{
    protected MaterialModel $model;
    protected OrderWorkModel $orderWork;
    protected WorkOrdersMaterialsModel $orderWorkAsignado;

    public function __construct(MaterialModel $asignarMaterial, OrderWorkModel $orderWork, WorkOrdersMaterialsModel $orderWorkAsignado)
    {
        $this->model = $asignarMaterial;
        $this->orderWork = $orderWork;
        $this->orderWorkAsignado = $orderWorkAsignado;
    }

    public function obtenerInstaladorId(){
        $userId = Auth::id();
        if (! $userId) {
            throw new RuntimeException('Usuario no autenticado.');
        }

        $instalador = InstaladorModel::where('identificador_usuario', $userId)->first();

        return $instalador->id_instalador;
    }




    /**
     * Obtener órdenes de trabajo asignadas al instalador del usuario autenticado.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     *
     * @throws RuntimeException si el usuario no está autenticado o no hay instalador asociado
     */
    public function getAssignedWorkOrders(int $perPage = 15): LengthAwarePaginator
    {
        $instalador = $this->obtenerInstaladorId();


        if (! $instalador) {
            // Opción A: devolver paginador vacío — aquí lanzamos excepción (elige según tu flujo)
            throw new RuntimeException('No se encontró un instalador para el usuario autenticado.');
        }

        return $this->orderWork
                    ->with('instalador')
                    ->where('tecnico_work_orders', $instalador)
                    ->paginate($perPage);
    }

    public function getOrdenTrabajoMateriales($id){
        $instalador = $this->obtenerInstaladorId();


        if (! $instalador) {
            // Opción A: devolver paginador vacío — aquí lanzamos excepción (elige según tu flujo)
            throw new RuntimeException('No se encontró un instalador para el usuario autenticado.');
        }

        return $this->orderWork
                    ->with('instalador')
                    ->where('tecnico_work_orders', $instalador)
                    ->where('id_work_order', $id)
                    ->first();

    }


    public function getMaterialesAsignados($id){


      $rows = DB::table('work_orders_materials as p')
            ->join('materiales as m', 'p.material_id', '=', 'm.id_material')
            ->where('p.work_order_id', $id)
            ->select('m.id_material','m.nombre_material','m.codigo_material','p.cantidad')
            ->get();

        // normalizar la respuesta (array plano)
        return $rows->map(function ($r) {
            return [
                'id' => $r->id_material,
                'codigo' => $r->codigo_material,
                'nombre' => $r->nombre_material,
                'cantidad' => $r->cantidad,
            ];
        })->toArray();
    }









}