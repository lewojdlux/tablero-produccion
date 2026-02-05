<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EventosExport implements WithMultipleSheets
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new ResumenSheet($this->filters),
            new EventosSheet($this->filters),
            new ActividadesSheet($this->filters),
        ];
    }
}
