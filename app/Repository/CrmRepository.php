<?php


namespace App\Repository;

// Models
use App\Models\CrmModel;


class CrmRepository
{

    protected CrmModel $modelCrm ;

    // constructor
    public function __construct( CrmModel $modelCrm )
    {
        $this->modelCrm = $modelCrm;
    }

    public function listCrm(int $page, int $perPage, array $filters): array
    {
        $offset = ($page - 1) * $perPage;

        return [
            'rows'  => $this->modelCrm::obtenerSeguimiento($offset, $perPage, $filters),
            'total' => $this->modelCrm::contarSeguimiento($filters),
            'totales' => $this->modelCrm::totalesOportunidades($filters),
            'totales_por_asesor' => $this->modelCrm::totalesOportunidadesPorAsesor($filters),
        ];
    }


    /* Listar Eventos / Visitas CRM */
    public function listEventos(int $page, int $perPage, array $filters): array
    {
        $offset = ($page - 1) * $perPage;

        return [
            'rows'  => $this->modelCrm::obtenerEventos($offset, $perPage, $filters),
            'total' => $this->modelCrm::contarEventos($filters),
            'totales' => $this->modelCrm::totalesEventos($filters),
            'totales_por_asesor' => $this->modelCrm::totalesPorAsesor($filters),
        ];
    }

    public function totalesPorAsesor(array $filters): array
    {
        return $this->modelCrm::totalesPorAsesor($filters);
    }

    /* ===== KPIs ===== */

    public function kpiResumen(array $filters)
    {
        return $this->modelCrm::kpiResumen($filters);
    }

    public function kpiPorActividad(array $filters)
    {
        return $this->modelCrm::kpiPorActividad($filters);
    }

    public function kpiPorAsesor(array $filters)
    {
        return $this->modelCrm::kpiPorAsesor($filters);
    }

    public function kpiPendientesDetalle(array $filters)
    {
        return $this->modelCrm::kpiPendientesDetalle($filters);
    }

    public function kpiCotizacionSinSeguimiento()
    {
        return $this->modelCrm::kpiCotizacionSinSeguimiento();
    }

}
