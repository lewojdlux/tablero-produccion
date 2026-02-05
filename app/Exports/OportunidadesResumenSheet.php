<?php

namespace App\Exports;

use App\Services\CrmService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OportunidadesResumenSheet implements FromArray, WithHeadings, WithTitle,  WithStyles
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

        $data = $service->listCrm(1, 1, $this->filters)['totales_por_asesor'];

        $rows = [];
        $totalOp = 0;
        $totalAct = 0;

        foreach ($data as $r) {
            $rows[] = [
                $r->asesor,
                $r->oportunidades,
                $r->actividades,
            ];
            $totalOp += $r->oportunidades;
            $totalAct += $r->actividades;
        }

        $rows[] = ['TOTAL', $totalOp, $totalAct];

        $this->totalRows = count($rows) + 1;

        return $rows;
    }

    public function headings(): array
    {
        return ['Asesor', 'Oportunidades', 'Actividades'];
    }

    public function styles(Worksheet $sheet)
    {
        /* HEADER */
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ]);

        /* CUERPO */
        $sheet->getStyle('A1:C' . $this->totalRows)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ]);

        /* TOTAL */
        $sheet->getStyle('A' . $this->totalRows . ':C' . $this->totalRows)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => 'E5E7EB'],
            ],
        ]);

        $sheet->getStyle('B2:C' . $this->totalRows)
            ->getAlignment()
            ->setHorizontal('center');
    }


    public function title(): string
    {
        return 'Resumen';
    }
}