<?php

namespace App\Exports;

use App\Services\CrmService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResumenSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $filters;
    protected int $totalRows = 0;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function array(): array
    {
        $service = app(CrmService::class);

        // 👇 usa el repository directamente (NO el service)
        $data = $service->listEventos(1, 1, $this->filters)['totales_por_asesor'];

        $rows = [];
        $totalEventos = 0;
        $totalActividades = 0;

        foreach ($data as $r) {
            $rows[] = [
                $r->asesor,
                $r->eventos,
                $r->actividades,
            ];
            $totalEventos += $r->eventos;
            $totalActividades += $r->actividades;
        }

        // TOTAL GENERAL
        $rows[] = [
            'TOTAL',
            $totalEventos,
            $totalActividades,
        ];

        $this->totalRows = count($rows) + 1; // + encabezado

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asesor',
            'Eventos',
            'Actividades'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // 🔹 Encabezados
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                ],
            ],
        ]);

        // 🔹 Todo el cuerpo
        $sheet->getStyle('A1:C' . $this->totalRows)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                ],
            ],
        ]);

        // 🔹 Fila TOTAL
        $sheet->getStyle('A' . $this->totalRows . ':C' . $this->totalRows)
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'color' => ['rgb' => 'E5E7EB'], // gris suave
                ],
            ]);

        // 🔹 Centrar números
        $sheet->getStyle('B2:C' . $this->totalRows)
            ->getAlignment()
            ->setHorizontal('center');
    }

    public function title(): string
    {
        return 'Resumen';
    }
}