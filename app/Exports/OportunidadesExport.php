<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OportunidadesExport implements WithMultipleSheets
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new OportunidadesResumenSheet($this->filters),
            new OportunidadesSheet($this->filters),
            new OportunidadesActividadesSheet($this->filters),
            new FiltrosSheet($this->filters),
        ];
    }
}