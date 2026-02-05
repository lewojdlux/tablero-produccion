<?php

namespace App\Exports;

use App\Services\CrmService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OportunidadesSheet implements FromArray, WithHeadings, WithTitle,  WithStyles
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
        $rows = $service->listCrm(1, 100000, $this->filters)['rows'];

        $map = [];

        foreach ($rows as $r) {
            if (!isset($map[$r->Identificador])) {
                $map[$r->Identificador] = [
                    $r->FechaRegistro,
                    $r->Identificador,
                    $r->Cliente,
                    $r->NombreAsesor,
                    $r->EstadoOportunidad,
                    $r->Etapa,
                    $r->SectorProyecto,
                    0,
                ];
            }
            $map[$r->Identificador][7]++;
        }


        $this->totalRows = count($map) + 1;
        return array_values($map);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Oportunidad',
            'Cliente',
            'Asesor',
            'Estado',
            'Etapa',
            'Sector',
            '# Actividades'
        ];
    }


    public function styles(Worksheet $sheet)
    {
        /* ===== ENCABEZADOS ===== */
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
                'wrapText'   => true,
            ],
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => 'E5E7EB'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin'],
            ],
        ]);

        /* ===== CUERPO ===== */
        $sheet->getStyle('A2:H' . $this->totalRows)->applyFromArray([
            'alignment' => [
                'vertical' => 'top',
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin'],
            ],
        ]);

        /* ===== CENTRADOS ===== */
        $sheet->getStyle('A2:A' . $this->totalRows)->getAlignment()->setHorizontal('center'); // Fecha
        $sheet->getStyle('B2:B' . $this->totalRows)->getAlignment()->setHorizontal('center'); // ID
        $sheet->getStyle('H2:H' . $this->totalRows)->getAlignment()->setHorizontal('center'); // # actividades

        /* ===== ALTURA FILAS ===== */
        foreach (range(1, $this->totalRows) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(22);
        }

        /* ===== ANCHOS ===== */
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(14);

        /* ===== CONGELAR HEADER ===== */
        $sheet->freezePane('A2');
    }


    public function title(): string
    {
        return 'Oportunidades';
    }
}