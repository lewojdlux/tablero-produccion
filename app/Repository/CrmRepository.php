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
        ];
    }

}