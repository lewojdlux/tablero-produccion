<?php

namespace App\Exports;

use App\Services\CrmService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EventosSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
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

        // Traer TODOS los eventos (sin paginar)
        $rows = $service->listEventos(1, 100000, $this->filters)['rows'];

        $map = [];

        foreach ($rows as $r) {
            if (!isset($map[$r->IntIdEvento])) {
                $map[$r->IntIdEvento] = [
                    $r->FechaRegistroDocumento,
                    $r->NombreAsesor,
                    $r->NombreTercero,
                    $r->TipoEvento,
                    $r->NitTercero,
                    $r->ReferenciaCliente,
                    $r->Observaciones,
                    0, // actividades
                ];
            }
            $map[$r->IntIdEvento][7]++;
        }

        $this->totalRows = count($map) + 1;

        return array_values($map);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Asesor',
            'Cliente',
            'Tipo Evento',
            'NIT',
            'Referencia',
            'Observaciones',
            '# Actividades'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezados
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ]);

        // Todo el cuerpo
        $sheet->getStyle('A1:H' . $this->totalRows)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ]);

        // Centrar columnas numéricas
        $sheet->getStyle('A2:A' . $this->totalRows)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('H2:H' . $this->totalRows)->getAlignment()->setHorizontal('center');

        // Congelar encabezado
        $sheet->freezePane('A2');
    }

    public function title(): string
    {
        return 'Eventos';
    }
}