<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OrdenTrabajoFinancieroExport implements FromArray, WithStyles, WithTitle
{
    protected $id;
    protected $totalRows = 0;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function array(): array
    {
        $orden = DB::table('work_orders')->where('id_work_order', $this->id)->first();

        $pedidoId = $orden->pedido;

        // Servicios OT (línea 140)
        $servicios = DB::connection('sqlsrv')
            ->table('TblDetalleDocumentos as d')
            ->join('TblProductos as p', 'p.StrIdProducto', '=', 'd.StrProducto')
            ->where('d.IntDocumento', $pedidoId)
            ->where('d.IntTransaccion', 109)
            ->where('p.StrLinea', 40)
            ->selectRaw(
                "
                p.StrIdProducto as codigo,
                p.StrDescripcion as descripcion,
                d.IntCantidad as cantidad,
                d.IntValorUnitario as valor_unitario,
                (d.IntCantidad * d.IntValorUnitario) - ISNULL(d.IntValorDescuento,0) as total
            ",
            )
            ->get();

        $pedidoTotal = $servicios->sum('total');

        $manoObra = DB::table('vw_calculo_mano_obra_ot')->where('id_work_order', $this->id)->get();

        $manoObraTotal = $manoObra->sum('total');

        $adicionales = DB::table('detalle_solicitud_material as d')
            ->join('pedidos_materiales as p', 'p.id_pedido_material', '=', 'd.solicitud_material_id')
            ->where('p.orden_trabajo_id', $this->id)
            ->selectRaw(
                "
                d.codigo_material,
                d.descripcion_material,
                d.cantidad,
                d.precio_unitario,
                d.descuento,
                (d.cantidad * d.precio_unitario) - IFNULL(d.descuento,0) as total
            ",
            )
            ->get();

        $adicionalTotal = $adicionales->sum('total');

        $utilidad = $pedidoTotal - $manoObraTotal - $adicionalTotal;

        $rows = [];

        // ENCABEZADO
        $rows[] = ['RESUMEN ORDEN DE TRABAJO'];
        $rows[] = [];
        $rows[] = ['N° OT', $orden->n_documento];
        $rows[] = ['N° Pedido', $pedidoId];
        $rows[] = [];

        // SERVICIOS OT
        $rows[] = ['SERVICIOS OT'];
        $rows[] = ['Código', 'Descripción', 'Cant', 'V.Unit', 'Total'];

        foreach ($servicios as $s) {
            $rows[] = [$s->codigo, $s->descripcion, $s->cantidad, $s->valor_unitario, $s->total];
        }

        $rows[] = ['', '', '', 'TOTAL PEDIDO', $pedidoTotal];
        $rows[] = [];

        // MANO DE OBRA
        $rows[] = ['MANO DE OBRA'];
        $rows[] = ['Instalador', 'Horas', 'Valor Hora', '', 'Total'];

        foreach ($manoObra as $m) {
            $instalador = $m->tipo . ' - ' . $m->nombre_instalador;
            $rows[] = [
                $instalador, 
                $m->horas, 
                $m->valor_hora, 
                '', 
                $m->total
            ];
        }

        $rows[] = ['', '', '', 'TOTAL MANO OBRA', $manoObraTotal];
        $rows[] = [];

        // ADICIONALES
        $rows[] = ['SERVICIOS ADICIONALES'];
        $rows[] = ['Código', 'Descripción', 'Cant', 'V.Unit / Desc', 'Total'];

        foreach ($adicionales as $a) {

            $valorTexto = '$' . number_format($a->precio_unitario, 2);

            if ($a->descuento > 0) {
                $valorTexto .= "\nDesc: $" . number_format($a->descuento, 2);
            }

    
            $rows[] = [
                $a->codigo_material, 
                $a->descripcion_material, 
                $a->cantidad, 
                $valorTexto,
                $a->total
            ];
        }

        $rows[] = ['', '', '', 'TOTAL ADICIONAL', $adicionalTotal];
        $rows[] = [];

        // RESUMEN FINAL
        $rows[] = ['RESUMEN'];
        $rows[] = ['', '', '', 'UTILIDAD', $utilidad];

        $this->totalRows = count($rows);
        return $rows;
    }
    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->totalRows;

        /*
    |--------------------------------------------------------------------------
    | TITULO PRINCIPAL
    |--------------------------------------------------------------------------
    */
        $sheet->mergeCells('A1:E1');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        /*
    |--------------------------------------------------------------------------
    | BORDES GENERALES
    |--------------------------------------------------------------------------
    */
        $sheet
            ->getStyle("A2:E{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        /*
    |--------------------------------------------------------------------------
    | ALINEACIONES FIJAS POR COLUMNA
    |--------------------------------------------------------------------------
    */

        // Código centrado
        $sheet
            ->getStyle("A1:A{$lastRow}")
            ->getAlignment()
            ->setHorizontal('center');

        // Cantidades centradas
        $sheet
            ->getStyle("C1:C{$lastRow}")
            ->getAlignment()
            ->setHorizontal('center')
            ;

        // Valores a la derecha
        $sheet
            ->getStyle("D1:E{$lastRow}")
            ->getAlignment()
            ->setHorizontal('right');


        // Permitir salto de línea en columna V.Unit / Desc
        $sheet
            ->getStyle("D1:D{$lastRow}")
            ->getAlignment()
            ->setWrapText(true);

        /*

        
    |--------------------------------------------------------------------------
    | FORMATO MONEDA
    |--------------------------------------------------------------------------
    */
        $sheet
            ->getStyle("E1:E{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0.00');

        $inManoObra = false;

        for ($row = 1; $row <= $lastRow; $row++) {

            $colA = $sheet->getCell("A{$row}")->getValue();
            $colC = $sheet->getCell("C{$row}")->getValue();

            // Cuando empieza el bloque
            if ($colA === 'MANO DE OBRA') {
                $inManoObra = true;
                continue;
            }

            // Cuando termina el bloque
            if ($colA === 'SERVICIOS ADICIONALES') {
                $inManoObra = false;
            }

            // Si estamos dentro del bloque MANO DE OBRA
            if ($inManoObra && is_numeric($colC)) {

                $sheet->getStyle("C{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');

                $sheet->getStyle("C{$row}")
                    ->getAlignment()
                    ->setHorizontal('right');
            }
        }
        

        /*
    |--------------------------------------------------------------------------
    | ESTILOS DINÁMICOS POR CONTENIDO
    |--------------------------------------------------------------------------
    */

        for ($row = 1; $row <= $lastRow; $row++) {
            $colA = $sheet->getCell("A{$row}")->getValue();
            $colD = $sheet->getCell("D{$row}")->getValue();

            // === TITULOS DE BLOQUE ===
            if (in_array($colA, ['SERVICIOS OT', 'MANO DE OBRA', 'SERVICIOS ADICIONALES', 'RESUMEN'])) {
                $sheet->mergeCells("A{$row}:E{$row}");

                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => 'left',
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB'],
                    ],
                ]);

                $sheet->getRowDimension($row)->setRowHeight(24);
            }

            // === ENCABEZADOS DE TABLA ===
            if (in_array($colA, ['Código', 'Instalador', 'Descripción'])) {
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => 'center',
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                ]);
            }

            // === TOTALES ===
            if (is_string($colD) && str_contains($colD, 'TOTAL')) {
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB'],
                    ],
                ]);
            }

            // === UTILIDAD DESTACADA ===
            if (is_string($colD) && str_contains($colD, 'UTILIDAD')) {
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D1FAE5'],
                    ],
                ]);
            }

            //$sheet->getRowDimension($row)->setRowHeight(22);
            $cellValue = $sheet->getCell("D{$row}")->getValue();

            if (is_string($cellValue) && str_contains($cellValue, "\n")) {
                // Si tiene salto de línea → altura automática
                $sheet->getRowDimension($row)->setRowHeight(-1);
            } else {
                // Si no → altura normal fija
                $sheet->getRowDimension($row)->setRowHeight(22);
            }
        }

        /*
    |--------------------------------------------------------------------------
    | ANCHOS PROFESIONALES
    |--------------------------------------------------------------------------
    */
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(55);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 45,
            'C' => 10,
            'D' => 15,
            'E' => 18,
        ];
    }

    public function title(): string
    {
        return 'Resumen Orden de Trabajo';
    }
}