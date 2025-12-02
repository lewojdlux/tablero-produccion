<?php

namespace App\Services;

use App\Repository\AsignarMaterialRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AsignarMaterialService
{
    protected $asignarMaterialRepository;

    public function __construct(AsignarMaterialRepository $asignarMaterialRepository)
    {
        $this->asignarMaterialRepository = $asignarMaterialRepository;
    }

    public function getAssignedWorkOrders(int $perPage = 15): LengthAwarePaginator
    {
        // Delega al repositorio — sin lógica innecesaria acá
        return $this->asignarMaterialRepository->getAssignedWorkOrders($perPage);
    }

    public function getOrdenTrabajoMaterialesId($id){
        return $this->asignarMaterialRepository->getOrdenTrabajoMateriales($id);
    }

    public function getMaterialesAsignados($id){
        return $this->asignarMaterialRepository->getMaterialesAsignados($id);
    }
}
