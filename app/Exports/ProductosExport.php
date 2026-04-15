<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductosExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(function ($m) {

            $stock = (int) $m->saldo_disponible;

            $precioBase = $m->precio_unitario ?? 0;

            $precioIVA = round($precioBase * 1.19);

            return [
                'codigo' => $m->codigo,
                'stock' => $stock,
                'precio unitario' => $precioBase,
                'precio' => $precioIVA,
            ];
        });
    }

    public function headings(): array
    {
        return ['codigo', 'stock', 'precio'];
    }
}
