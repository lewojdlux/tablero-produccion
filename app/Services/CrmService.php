<?php


namespace App\Services;


// Repositories
use App\Repository\CrmRepository;


class CrmService
{
    protected CrmRepository $crmRepository;

    public function __construct(CrmRepository $crmRepository)
    {
        $this->crmRepository = $crmRepository;
    }

    /* Función listar CRM de todos los asesores activos */
    public function listCrm(int $page, int $perPage, array $filters  ): array
    {
        return $this->crmRepository->listCrm($page, $perPage , $filters);
    }

    public function kpisSeguimiento(array $filters): array
    {
        return [
            'resumen'        => $this->crmRepository->kpiResumen($filters),
            'porActividad'   => $this->crmRepository->kpiPorActividad($filters),
            'porAsesor'      => $this->crmRepository->kpiPorAsesor($filters),
            'pendientes'     => $this->crmRepository->kpiPendientesDetalle($filters),
            'cotizacionRisk' => $this->crmRepository->kpiCotizacionSinSeguimiento(),
        ];
    }

    // Listar Eventos / Visitas CRM
    public function listEventos(int $page, int $perPage, array $filters): array
    {
        return $this->crmRepository->listEventos($page, $perPage, $filters);
    }

    public function totalesPorAsesor(array $filters): array
    {
        return $this->crmRepository->totalesPorAsesor($filters);
    }

}
