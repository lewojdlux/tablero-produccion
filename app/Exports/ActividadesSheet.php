<?php

namespace App\Exports;

use App\Services\CrmService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActividadesSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
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
        $rows = $service->listEventos(1, 100000, $this->filters)['rows'];

        $data = [];

        foreach ($rows as $r) {
            $data[] = [
                $r->FechaRegistroDocumento,
                $r->FechaRegistroActividad,
                $r->NombreAsesor,
                $r->NombreTercero,
                $r->TipoEvento,
                $r->TipoActividad,
                $r->DetalleActividad,
            ];
        }

        $this->totalRows = count($data) + 1;

        return $data;
    }

    public function headings(): array
    {
        return [
            'Fecha Evento',
            'Fecha Actividad',
            'Asesor',
            'Cliente',
            'Evento',
            'Actividad',
            'Detalle'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezados
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ]);

        // Bordes generales
        $sheet->getStyle('A1:G' . $this->totalRows)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ]);

        // Centrar fechas
        $sheet->getStyle('A2:B' . $this->totalRows)->getAlignment()->setHorizontal('center');

        // Congelar encabezado
        $sheet->freezePane('A2');
    }

    public function title(): string
    {
        return 'Actividades';
    }
}
