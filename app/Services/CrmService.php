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

}
