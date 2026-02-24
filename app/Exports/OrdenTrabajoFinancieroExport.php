<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Services\CalculoManoObraService;

class OrdenTrabajoFinancieroExport implements FromArray, WithStyles, WithTitle
{
    protected $id;
    protected $totalRows = 1;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function array(): array
    {
        $rows = [];

        try {
            $calculoService = app(CalculoManoObraService::class);

            $orden = DB::table('work_orders')->where('id_work_order', $this->id)->first();

            if (!$orden) {
                $rows[] = ['ERROR: Orden no encontrada'];
                $this->totalRows = count($rows);
                return $rows;
            }

            $pedidoId = $orden->pd_servicio;
            $pedidoGlobal = $orden->pedido;

            // =========================
            // SERVICIOS OT
            // =========================
            $servicios = collect();

            try {

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
            } catch (\Throwable $e) {
                $servicios = collect();
            }

            $pedidoTotal = $servicios->sum('total');

            // =========================
            // MANO DE OBRA
            // =========================
            $principal = DB::table('orden_trabajo_jornadas as otj')->join('work_orders as wo', 'wo.id_work_order', '=', 'otj.orden_trabajo_id')
            ->join('instalador as i', 'i.id_instalador', '=', 'wo.instalador_id')
            ->where('otj.orden_trabajo_id', $this->id)
            ->select('otj.fecha', 'otj.hora_inicio', 'otj.hora_fin', 'i.id_instalador', 'i.nombre_instalador', 'i.valor_hora')
            ->get();

           // =========================
            // MANO DE OBRA
            // =========================

            $principal = DB::table('orden_trabajo_jornadas as otj')
                ->join('work_orders as wo', 'wo.id_work_order', '=', 'otj.orden_trabajo_id')
                ->join('instalador as i', 'i.id_instalador', '=', 'wo.instalador_id')
                ->where('otj.orden_trabajo_id', $this->id)
                ->select(
                    'otj.fecha',
                    'otj.hora_inicio',
                    'otj.hora_fin',
                    'i.id_instalador',
                    'i.nombre_instalador',
                    'i.valor_hora'
                )
                ->get();

            $acompanantes = DB::table('orden_trabajo_jornadas as otj')
                ->whereNotNull('otj.acompanante_ot')
                ->join('instalador as ia', function ($join) {
                    $join->whereRaw("
                        JSON_CONTAINS(
                            otj.acompanante_ot,
                            CONCAT('[', ia.id_instalador, ']')
                        )
                    ");
                })
                ->where('otj.orden_trabajo_id', $this->id)
                ->select(
                    'otj.fecha',
                    'otj.hora_inicio',
                    'otj.hora_fin',
                    'ia.id_instalador',
                    'ia.nombre_instalador',
                    'ia.valor_hora'
                )
                ->get();

            // unir principal + acompañantes
            $registros = $principal->merge($acompanantes);

            $detalle = collect();

            foreach ($registros as $r) {

                if (!$r->hora_inicio || !$r->hora_fin) continue;

                $calculo = $calculoService->calcularPagoJornada(
                    $r->fecha,
                    $r->hora_inicio,
                    $r->hora_fin,
                    $r->valor_hora
                );

                foreach ($calculo as $c) {
                    if ($c['horas'] > 0) {
                        $detalle->push([
                            'id_instalador' => $r->id_instalador,
                            'nombre_instalador' => $r->nombre_instalador,
                            'tipo' => $c['tipo'],
                            'horas' => $c['horas'],
                            'valor_hora' => $c['valor_hora'],
                            'total' => $c['total'],
                        ]);
                    }
                }
            }

            $manoObra = $detalle
                ->groupBy(fn($item) => $item['id_instalador'].'_'.$item['tipo'])
                ->map(function ($items) {
                    return (object)[
                        'nombre_instalador' => $items->first()['nombre_instalador'],
                        'tipo' => $items->first()['tipo'],
                        'horas' => round($items->sum('horas'), 2),
                        'valor_hora' => $items->first()['valor_hora'],
                        'total' => round($items->sum('total'), 2),
                    ];
                })
                ->values();

            $manoObraTotal = $manoObra->sum('total');

            // =========================
            // SERVICIOS ADICIONALES
            // =========================
            $adicionales = DB::table('work_orders_materials')->where('work_order_id', $this->id)->get();

            $adicionalTotal = 0;

            // =========================
            // ARMAR FILAS
            // =========================
            $rows[] = ['RESUMEN ORDEN DE TRABAJO'];
            $rows[] = [];
            $rows[] = ['N° OT', $orden->n_documento];
            $rows[] = ['N° Pedido Global', $pedidoGlobal];
            $rows[] = ['N° Pedido Servicio', $pedidoId];
            $rows[] = [];

            // SERVICIOS
            $rows[] = ['SERVICIOS OT'];
            $rows[] = ['Código', 'Descripción', 'Cant', 'V.Unit', 'Total'];

            foreach ($servicios as $s) {
                $rows[] = [$s->codigo, $s->descripcion, (float) $s->cantidad, (float) $s->valor_unitario, (float) $s->total];
            }

            $rows[] = ['', '', '', 'TOTAL PEDIDO', (float) $pedidoTotal];
            $rows[] = [];

            // MANO DE OBRA
            $rows[] = ['MANO DE OBRA'];
            $rows[] = ['Instalador', 'Tipo Hora', 'Horas', 'Valor Hora', 'Total'];

            foreach ($manoObra as $m) {
                $rows[] = [$m->nombre_instalador, $m->tipo, (float) $m->horas, (float) $m->valor_hora, (float) $m->total];
            }

            $rows[] = ['', '', '', 'TOTAL MANO OBRA', (float) $manoObraTotal];
            $rows[] = [];

            // ADICIONALES
            $rows[] = ['SERVICIOS ADICIONALES'];
            $rows[] = ['Código', 'Descripción', 'Cant', 'V.Unit', 'Total'];

            foreach ($adicionales as $a) {
                $cantidad = (float) ($a->cantidad ?? 1);
                $valor = (float) ($a->ultimo_costo ?? 0);
                $total = $cantidad * $valor;

                $adicionalTotal += $total;

                $rows[] = [$a->material_id, $a->descripcion_material ?? '', $cantidad, $valor, $total];
            }

            $rows[] = ['', '', '', 'TOTAL ADICIONAL', (float) $adicionalTotal];
            $rows[] = [];

            $utilidad = $pedidoTotal - $manoObraTotal - $adicionalTotal;

            $rows[] = ['RESUMEN'];
            $rows[] = ['', '', '', 'UTILIDAD', (float) $utilidad];

            $this->totalRows = max(count($rows), 1);

            return $rows;
        } catch (\Throwable $e) {
            $rows[] = ['ERROR GENERANDO EXPORT'];
            $rows[] = [$e->getMessage()];
            $this->totalRows = count($rows);

            return $rows;
        }
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max($this->totalRows, 1);

        /*
    |--------------------------------------------------
    | TITULO PRINCIPAL
    |--------------------------------------------------
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
    |--------------------------------------------------
    | BORDES GENERALES
    |--------------------------------------------------
    */
        $sheet
            ->getStyle("A1:E{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        /*
    |--------------------------------------------------
    | ALINEACIONES
    |--------------------------------------------------
    */
        $sheet
            ->getStyle("C1:C{$lastRow}")
            ->getAlignment()
            ->setHorizontal('center');

        $sheet
            ->getStyle("D1:E{$lastRow}")
            ->getAlignment()
            ->setHorizontal('right');

        /*
    |--------------------------------------------------
    | FORMATO MONEDA
    |--------------------------------------------------
    */
        $sheet
            ->getStyle("D1:E{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0.00');

        /*
    |--------------------------------------------------
    | ESTILOS DINÁMICOS POR BLOQUE
    |--------------------------------------------------
    */

        for ($row = 1; $row <= $lastRow; $row++) {
            $colA = $sheet->getCell("A{$row}")->getValue();
            $colD = $sheet->getCell("D{$row}")->getValue();

            // BLOQUES TITULO
            if (in_array($colA, ['SERVICIOS OT', 'MANO DE OBRA', 'SERVICIOS ADICIONALES', 'RESUMEN'])) {
                $sheet->mergeCells("A{$row}:E{$row}");

                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB'], // gris claro
                    ],
                ]);

                $sheet->getRowDimension($row)->setRowHeight(22);
            }

            // ENCABEZADOS DE TABLA
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

            // TOTALES
            if (is_string($colD) && str_contains($colD, 'TOTAL')) {
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB'],
                    ],
                ]);
            }

            // UTILIDAD VERDE
            if (is_string($colD) && str_contains($colD, 'UTILIDAD')) {
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'C6EFCE'], // verde claro
                    ],
                ]);
            }

            $sheet->getRowDimension($row)->setRowHeight(20);
        }

        /*
    |--------------------------------------------------
    | ANCHOS PROFESIONALES
    |--------------------------------------------------
    */
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(55);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
    }

    public function title(): string
    {
        return 'Resumen Orden de Trabajo';
    }
}