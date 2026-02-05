<?php

namespace App\Exports;

use App\Services\CrmService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class OportunidadesActividadesSheet implements FromArray, WithHeadings, WithTitle
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function array(): array
    {
        $service = app(CrmService::class);
        $rows = $service->listCrm(1, 100000, $this->filters)['rows'];

        $data = [];

        foreach ($rows as $r) {
            $data[] = [
                $r->FechaRegistro,
                $r->Identificador,
                $r->Cliente,
                $r->NombreAsesor,
                $r->Actividad,
                $r->FechaActividad,
                $r->Observacion,
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Oportunidad',
            'Cliente',
            'Asesor',
            'Actividad',
            'Fecha Actividad',
            'Detalle'
        ];
    }

    public function title(): string
    {
        return 'Actividades';
    }
}