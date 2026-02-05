<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class FiltrosSheet implements FromArray, WithTitle
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function array(): array
    {
        return [
            ['Exportado por', Auth::user()->name ?? Auth::user()->username],
            ['Fecha exportación', now()->format('Y-m-d H:i:s')],
            ['Desde', $this->filters['start'] ?? '—'],
            ['Hasta', $this->filters['end'] ?? '—'],
            ['Tipo Evento', $this->filters['tipoEvento'] ?? 'Todos'],
            ['Asesores', empty($this->filters['asesores'])
                ? 'Todos'
                : implode(', ', $this->filters['asesores'])
            ],
        ];
    }

    public function title(): string
    {
        return 'Filtros';
    }
}
