<?php

namespace App\Repository;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\ProductionOrder;

class ProductionRepository
{
    protected $conn = 'sqlsrv';

    // Listado de órdenes de trabajo (documentos 140) con datos relacionados de cliente, vendedor, posible factura y estado de facturación. Si se pasan filtros de fecha, se traducen a filtros por año/mes del documento.
    public function searchOrders(array $filters)
    {
        $ano = isset($filters['ano']) && (int) $filters['ano'] > 0 ? (int) $filters['ano'] : (int) now()->year;
        $mes = isset($filters['mes']) && (int) $filters['mes'] >= 1 && (int) $filters['mes'] <= 12 ? (int) $filters['mes'] : (int) now()->month;

        // Parseo robusto del datepicker (prioriza dd/mm/aaaa)
        $parse = function (?string $s) {
            if (!$s) {
                return null;
            }
            foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'Ymd'] as $fmt) {
                try {
                    return \Carbon\Carbon::createFromFormat($fmt, $s);
                } catch (\Throwable $e) {
                }
            }
            try {
                return \Carbon\Carbon::parse($s);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $start = !empty($filters['start']) ? $parse($filters['start'])?->startOfDay() : null;
        $end = !empty($filters['end']) ? $parse($filters['end'])?->endOfDay() : null;
        if ($start && $end && $end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $q = \DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblDocumentos as tt', function ($j) {
                $j->on('tt.IntDocumento', '=', 't.IntPedido')->where('tt.IntTransaccion', 109)->where('tt.IntEstado', 0);
            })
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->join('TblVendedores as tv', 't.StrDVendedor', '=', 'tv.StrIdVendedor')
            /*->join('TblDetalleDocumentos as ttd', function ($j) {
                $j->on('t.IntTransaccion', '=', 'ttd.IntTransaccion')->on('t.IntDocumento', '=', 'ttd.IntDocumento');
            })*/
            ->leftJoin('TblDocumentos as tf', function ($j) {
                $j->on('tf.IntDocRef', '=', 't.IntDocumento')->where('tf.IntTransaccion', 104)->where('tf.IntEstado', 0);
            })
            ->leftJoin('TblDetalleDocumentos as tdf', function ($j) {
                $j->on('tf.IntTransaccion', '=', 'tdf.IntTransaccion')->on('tf.IntDocumento', '=', 'tdf.IntDocumento');
            })
            ->where('t.IntTransaccion', 140)
            ->where('t.IntEstado', 0);

        if ($start || $end) {
            // Hay rango → traducir a (Año, Mes)
            $sY = $start?->year;
            $sM = $start?->month;
            $eY = $end?->year;
            $eM = $end?->month;

            // Si falta alguno, completa con el otro extremo
            if (!$start && $end) {
                $sY = $eY;
                $sM = 1;
            } // desde enero del año fin
            if ($start && !$end) {
                $eY = $sY;
                $eM = 12;
            } // hasta dic del año inicio

            $q->where(function ($w) use ($sY, $sM, $eY, $eM) {
                if ($sY === $eY) {
                    // Mismo año
                    $w->where('t.IntAno', $sY)->whereBetween('t.IntPeriodo', [$sM, $eM]);
                } else {
                    // Año inicial (mes >= m1)
                    $w->where(function ($w1) use ($sY, $sM) {
                        $w1->where('t.IntAno', $sY)->where('t.IntPeriodo', '>=', $sM);
                    })
                        // Años intermedios completos
                        ->orWhere(function ($w2) use ($sY, $eY) {
                            if ($sY + 1 <= $eY - 1) {
                                $w2->whereBetween('t.IntAno', [$sY + 1, $eY - 1]);
                            } else {
                                // No hay años intermedios; evita condición vacía
                                $w2->whereRaw('1=0');
                            }
                        })
                        // Año final (mes <= m2)
                        ->orWhere(function ($w3) use ($eY, $eM) {
                            $w3->where('t.IntAno', $eY)->where('t.IntPeriodo', '<=', $eM);
                        });
                }
            });
        } else {
            // Sin rango → usa los filtros "mes/año" directos
            $q->where('t.IntAno', $ano)->where('t.IntPeriodo', $mes);
        }

        // Filtros opcionales
        $q->when(!empty($filters['asesor']) && strlen($filters['asesor']) >= 2, fn($qq) => $qq->where('tv.StrNombre', 'like', '%' . $filters['asesor'] . '%'))
            ->when(!empty($filters['cliente']) && strlen($filters['cliente']) >= 2, fn($qq) => $qq->where('tc.StrNombre', 'like', '%' . $filters['cliente'] . '%'))
            ->when(!empty($filters['pedido']), fn($qq) => $qq->where('t.IntPedido', $filters['pedido']));

        $rows = $q
            ->selectRaw(
                "
                t.IntTransaccion as TipoTransaccion,
                t.IntDocumento   as Ndocumento,
                t.IntPedido      as Pedido,
                tc.StrNombre     as Tercero,
                tv.StrNombre     as Vendedor,

                t.StrObservaciones as Observaciones,
                t.IntPeriodo     as Periodo,
                t.IntAno         as Ano,
                t.DatFecha       as FechaOrdenProduccion,
                tv.StrIdVendedor  as VendedorUsername

            ",
            )
            ->groupBy('t.IntTransaccion', 't.IntDocumento', 't.IntPedido', 'tc.StrNombre', 'tv.StrNombre', 't.StrObservaciones', 't.IntPeriodo', 't.IntAno', 't.DatFecha', 'tv.StrIdVendedor')
            ->orderBy('t.IntAno', 'DESC')
            ->orderBy('t.IntPeriodo', 'DESC')
            ->orderBy('t.IntDocumento', 'DESC')
            ->get();

        return $rows->map(fn($r) => (array) $r)->toArray();
    }

    // Detalle de OP (109) con posible enlace a factura (104) y su estado, sin filtrar por producto en el detalle. Si no se encuentra el documento, devuelve header vacío y lines vacío.
    public function getOrderDetail(string|int $ndoc, string $producto): array
    {
        $rows = DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblDocumentos as tt', function ($j) {
                $j->on('tt.IntDocumento', '=', 't.IntPedido')->where('tt.IntTransaccion', '=', 109)->where('tt.IntEstado', '=', 0);
            })
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->join('TblVendedores as tv', 't.StrDVendedor', '=', 'tv.StrIdVendedor')
            ->join('TblDetalleDocumentos as ttd', function ($j) {
                $j->on('t.IntTransaccion', '=', 'ttd.IntTransaccion')->on('t.IntDocumento', '=', 'ttd.IntDocumento');
            })
            ->leftJoin('TblDocumentos as tf', function ($j) {
                $j->on('tf.IntDocRef', '=', 't.IntDocumento')->where('tf.IntTransaccion', '=', 104)->where('tf.IntEstado', '=', 0);
            })
            ->leftJoin('TblDetalleDocumentos as tdf', function ($j) {
                $j->on('tf.IntTransaccion', '=', 'tdf.IntTransaccion')->on('tf.IntDocumento', '=', 'tdf.IntDocumento');
            })
            ->where('t.IntTransaccion', '140')
            ->where('t.IntDocumento', $ndoc)

            // <--- filtro por documento
            // ->where('ttd.StrProducto', $producto) // <--- filtro por producto
            ->where('t.IntEstado', 0)

            ->selectRaw(
                "
                    t.IntTransaccion as TipoTransaccion,
                    t.IntDocumento   as Ndocumento,
                    t.IntPedido      as Pedido,
                    tc.StrNombre     as Tercero,
                    tv.StrNombre     as Vendedor,
                    ttd.StrProducto  as Luminaria,
                    ttd.IntCantidad  as Cantidad,
                    t.StrObservaciones as Observaciones,
                    t.IntPeriodo     as Periodo,
                    t.IntAno         as Ano,
                    t.DatFecha       as FechaOrdenProduccion,
                    tv.StrIdVendedor  as VendedorUsername,
                    CASE
                        WHEN EXISTS (
                            SELECT TOP 1 StrProducto
                            FROM TblDetalleDocumentos d
                            WHERE d.StrProducto = ttd.StrProducto
                            AND d.IntTransaccion = 104
                        ) THEN 'FACTURADO' ELSE 'NO FACTURADO'
                    END AS EstadoFactura,
                    (
                        SELECT TOP 1 tdoc.IntDocumento
                        FROM TblDetalleDocumentos tdoc
                        WHERE tdoc.IntDocRefD   = t.IntPedido
                        AND tdoc.StrProducto  = ttd.StrProducto
                        AND tdoc.IntTransaccion = 104
                    ) AS NFactura
                ",
            )
            ->groupBy('t.IntTransaccion', 't.IntDocumento', 't.IntPedido', 'tc.StrNombre', 'tv.StrNombre', 'ttd.StrProducto', 'ttd.IntCantidad', 't.StrObservaciones', 't.IntPeriodo', 't.IntAno', 't.DatFecha', 'tv.StrIdVendedor')
            ->orderBy('t.IntPeriodo', 'DESC')
            ->get();

        if ($rows->isEmpty()) {
            return ['header' => [], 'lines' => []];
        }

        $first = (array) $rows->first();

        $header = [
            'Ndocumento' => $first['Ndocumento'] ?? null,
            'Pedido' => $first['Pedido'] ?? null,
            'Tercero' => $first['Tercero'] ?? null,
            'Vendedor' => $first['Vendedor'] ?? null,
            'VendedorUsername' => $first['VendedorUsername'] ?? null,
            'FechaOrdenProduccion' => $first['FechaOrdenProduccion'] ?? null,
            'Periodo' => $first['Periodo'] ?? null,
            'Ano' => $first['Ano'] ?? null,
            'Observaciones' => $first['Observaciones'] ?? null,
        ];

        $lines = $rows
            ->map(function ($r) {
                return [
                    'Luminaria' => $r->Luminaria ?? null,
                    'Cantidad' => $r->Cantidad ?? null,
                    'EstadoFactura' => $r->EstadoFactura ?? null,
                    'NFactura' => $r->NFactura ?? null,
                ];
            })
            ->values()
            ->all();

        return ['header' => $header, 'lines' => $lines];
    }

    // Detalle de ficha de producción (141) con posible enlace a OP padre (140) y sus insumos desde TblEnsamble. Si se pasa producto, filtra solo ese insumo.
    public function getOrderDetailFicha(string|int $ndoc): array
    {
        $conn = \DB::connection('sqlsrv');

        // Header: ficha (141) + posible ensamble padre (140) enlazado por IntPedido
        $headerRow = $conn
            ->table('TblDocumentos as t')
            ->leftJoin('TblDocumentos as p', function ($j) {
                $j->on('p.IntDocumento', '=', 't.IntPedido')->where('p.IntTransaccion', 140)->where('p.IntEstado', 0);
            })
            ->leftJoin('TblTerceros as ptc', 'p.StrTercero', '=', 'ptc.StrIdTercero')
            ->leftJoin('TblVendedores as ptv', 'p.StrDVendedor', '=', 'ptv.StrIdVendedor')
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->join('TblVendedores as tv', 't.StrDVendedor', '=', 'tv.StrIdVendedor')
            ->where('t.IntTransaccion', 141)
            ->where('t.IntDocumento', $ndoc)
            ->where('t.IntEstado', 0)
            ->selectRaw(
                "
            t.DatFecha               as FechaFicha,
            t.IntDocumento          as FichaDocumento,
            t.IntPedido             as PedidoAsociadoFicha,
            tc.StrNombre            as ClienteFicha,
            tv.StrNombre            as VendedorFicha,
            p.IntDocumento          as EnsambleDocumento,     -- documento 140 (orden de producción)
            p.IntPedido             as EnsamblePedido,        -- pedido del ensamble (si aplica)
            ptc.StrNombre           as ClienteEnsamble,
            ptv.StrNombre           as VendedorEnsamble,
            p.DatFecha              as FechaEnsamble
        ",
            )
            ->first();

        if (!$headerRow) {
            return ['header' => [], 'lines' => []];
        }

        // Líneas: detalle de la ficha (141)
        $lines = $conn
            ->table('TblDetalleDocumentos as td')
            ->where('td.IntTransaccion', 141)
            ->where('td.IntDocumento', $ndoc)
            ->select('td.IntDocumento as DetalleDocumento', 'td.StrProducto as Producto', 'td.IntCantidad as Cantidad')
            ->orderBy('td.StrProducto')
            ->get()
            ->map(
                fn($r) => [
                    'DetalleDocumento' => $r->DetalleDocumento,
                    'Luminaria' => $r->Producto,
                    'Cantidad' => $r->Cantidad,
                ],
            )
            ->values()
            ->all();

        // Normaliza el header a array
        $hdr = [
            'FechaFicha' => $headerRow->FechaFicha ?? null,
            'FichaDocumento' => $headerRow->FichaDocumento,
            'PedidoAsociadoFicha' => $headerRow->PedidoAsociadoFicha,
            'ClienteFicha' => $headerRow->ClienteFicha,
            'VendedorFicha' => $headerRow->VendedorFicha,
            'EnsambleDocumento' => $headerRow->EnsambleDocumento,
            'EnsamblePedido' => $headerRow->EnsamblePedido,
            'ClienteEnsamble' => $headerRow->ClienteEnsamble,
            'VendedorEnsamble' => $headerRow->VendedorEnsamble,
            'FechaEnsamble' => $headerRow->FechaEnsamble?->format('Y-m-d') ?? (string) $headerRow->FechaEnsamble,
        ];

        return ['header' => $hdr, 'lines' => $lines];
    }

    /** Crea la OP local (si no existe) desde la fuente ERP por OP + producto */
    public function enqueueFromErp(int|string $op, string $producto): array
    {
        // OBTIENE TODAS LAS LÍNEAS DEL PEDIDO (no filtra por producto porque está comentado)
        $data = $this->getOrderDetail($op, $producto);

        if (empty($data['header'])) {
            throw new \RuntimeException("No se encontró detalle en ERP para la OP {$op}");
        }

        if (empty($data['lines'])) {
            throw new \RuntimeException("El pedido {$op} no tiene líneas");
        }

        $result = null;

        // *** ESTA ES LA PARTE NUEVA ***
        foreach ($data['lines'] as $line) {
            // header + line
            $r = array_merge($data['header'], $line);

            // acá se hace el merge de todas las luminarias en JSON
            $result = $this->storeQueued($r);
        }

        return $result;
    }

    /** Inserta si no existe (OP + Luminaria únicos). Devuelve ['status' => created|exists, 'model' => ProductionOrder] */
    protected function storeQueued(array $r): array
    {
        return DB::transaction(function () use ($r) {
            // Buscar OP del documento (sin comparar luminaria)
            $existing = ProductionOrder::where('n_documento', $r['Ndocumento'])->first();

            if ($existing) {
                // Decodificar luminarias existentes
                $current = json_decode($existing->luminaria, true);

                // Si aún no es array, inicializar
                if (!is_array($current)) {
                    $current = [];
                }

                // Agregar luminaria si no está repetida
                if (!in_array($r['Luminaria'], $current)) {
                    $current[] = $r['Luminaria'];

                    $existing->update([
                        'luminaria' => json_encode($current, JSON_UNESCAPED_UNICODE),
                    ]);
                }

                return ['status' => 'updated', 'model' => $existing];
            }

            // Crear OP nueva con luminaria como array JSON
            $po = ProductionOrder::create([
                'ticket_code' => $this->nextTicketCode(),
                'tipo_transaccion' => $r['TipoTransaccion'] ?? null,
                'n_documento' => $r['Ndocumento'],
                'pedido' => $r['Pedido'] ?? null,
                'tercero' => $r['Tercero'] ?? null,
                'vendedor' => $r['Vendedor'] ?? null,
                'vendedor_username' => $r['VendedorUsername'] ?? null,

                // Guardar luminaria en formato JSON (múltiples)
                'luminaria' => json_encode([$r['Luminaria']], JSON_UNESCAPED_UNICODE),

                'observaciones' => $r['Observaciones'] ?? null,
                'periodo' => $r['Periodo'] ?? null,
                'ano' => $r['Ano'] ?? null,
                'fecha_orden_produccion' => $r['FechaOrdenProduccion'] ?? null,
                'estado_factura' => $r['EstadoFactura'] ?? null,
                'n_factura' => $r['NFactura'] ?? null,

                'status' => 'queued',
                'queued_at' => now(),
            ]);

            return ['status' => 'created', 'model' => $po];
        });
    }

    /** Genera ticket tipo YYMMDD-#### reiniciando cada día */
    protected function nextTicketCode(): string
    {
        $today = Carbon::now()->format('ymd'); // 250829
        $seq = ProductionOrder::whereDate('created_at', Carbon::today())->count() + 1;
        return sprintf('%s-%04d', $today, $seq); // 250829-0007
    }

    /*   PARA O.T   */ /////
    /*public function searchOrdersVentas(array $filters)
    {
        $ano = isset($filters['ano']) && (int) $filters['ano'] > 0 ? (int) $filters['ano'] : (int) now()->year;
        $mes = isset($filters['mes']) && (int) $filters['mes'] >= 1 && (int) $filters['mes'] <= 12 ? (int) $filters['mes'] : (int) now()->month;

        // Parseo robusto del datepicker (prioriza dd/mm/aaaa)
        $parse = function (?string $s) {
            if (!$s) {
                return null;
            }
            foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'Ymd'] as $fmt) {
                try {
                    return \Carbon\Carbon::createFromFormat($fmt, $s);
                } catch (\Throwable $e) {
                }
            }
            try {
                return \Carbon\Carbon::parse($s);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $start = !empty($filters['start']) ? $parse($filters['start'])?->startOfDay() : null;
        $end = !empty($filters['end']) ? $parse($filters['end'])?->endOfDay() : null;
        if ($start && $end && $end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $q = \DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblDocumentos as tt', function ($j) {
                $j->on('tt.IntDocumento', '=', 't.IntPedido')->where('tt.IntTransaccion', 109)->where('tt.IntEstado', 0);
            })
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->join('TblVendedores as tv', 't.StrDVendedor', '=', 'tv.StrIdVendedor')
            ->join('TblDetalleDocumentos as ttd', function ($j) {
                $j->on('t.IntTransaccion', '=', 'ttd.IntTransaccion')->on('t.IntDocumento', '=', 'ttd.IntDocumento');
            })
            ->leftJoin('TblDocumentos as tf', function ($j) {
                $j->on('tf.IntDocRef', '=', 't.IntDocumento')->where('tf.IntTransaccion', 104)->where('tf.IntEstado', 0);
            })
            ->leftJoin('TblDetalleDocumentos as tdf', function ($j) {
                $j->on('tf.IntTransaccion', '=', 'tdf.IntTransaccion')->on('tf.IntDocumento', '=', 'tdf.IntDocumento');
            })
            ->where('t.IntTransaccion', 140)
            ->where('t.IntEstado', 0);

        if ($start || $end) {
            // Hay rango → traducir a (Año, Mes)
            $sY = $start?->year;
            $sM = $start?->month;
            $eY = $end?->year;
            $eM = $end?->month;

            // Si falta alguno, completa con el otro extremo
            if (!$start && $end) {
                $sY = $eY;
                $sM = 1;
            } // desde enero del año fin
            if ($start && !$end) {
                $eY = $sY;
                $eM = 12;
            } // hasta dic del año inicio

            $q->where(function ($w) use ($sY, $sM, $eY, $eM) {
                if ($sY === $eY) {
                    // Mismo año
                    $w->where('t.IntAno', $sY)->whereBetween('t.IntPeriodo', [$sM, $eM]);
                } else {
                    // Año inicial (mes >= m1)
                    $w->where(function ($w1) use ($sY, $sM) {
                        $w1->where('t.IntAno', $sY)->where('t.IntPeriodo', '>=', $sM);
                    })
                        // Años intermedios completos
                        ->orWhere(function ($w2) use ($sY, $eY) {
                            if ($sY + 1 <= $eY - 1) {
                                $w2->whereBetween('t.IntAno', [$sY + 1, $eY - 1]);
                            } else {
                                // No hay años intermedios; evita condición vacía
                                $w2->whereRaw('1=0');
                            }
                        })
                        // Año final (mes <= m2)
                        ->orWhere(function ($w3) use ($eY, $eM) {
                            $w3->where('t.IntAno', $eY)->where('t.IntPeriodo', '<=', $eM);
                        });
                }
            });
        } else {
            // Sin rango → usa los filtros "mes/año" directos
            $q->where('t.IntAno', $ano)->where('t.IntPeriodo', $mes);
        }

        // Filtros opcionales
        $q->when(!empty($filters['asesor']) && strlen($filters['asesor']) >= 2, fn($qq) => $qq->where('tv.StrNombre', 'like', '%' . $filters['asesor'] . '%'))
            ->when(!empty($filters['cliente']) && strlen($filters['cliente']) >= 2, fn($qq) => $qq->where('tc.StrNombre', 'like', '%' . $filters['cliente'] . '%'))
            ->when(!empty($filters['pedido']), fn($qq) => $qq->where('t.IntPedido', $filters['pedido']));

        $rows = $q
            ->selectRaw(
                "
                t.IntTransaccion as TipoTransaccion,
                t.IntDocumento   as Ndocumento,
                t.IntPedido      as Pedido,
                tc.StrNombre     as Tercero,
                tv.StrNombre     as Vendedor,
                ttd.StrProducto  as Luminaria,
                t.StrObservaciones as Observaciones,
                t.IntPeriodo     as Periodo,
                t.IntAno         as Ano,
                t.DatFecha       as FechaOrdenProduccion,
                tv.StrIdVendedor  as VendedorUsername,
                CASE WHEN EXISTS (
                    SELECT TOP 1 1
                    FROM TblDetalleDocumentos d
                    WHERE d.StrProducto = ttd.StrProducto
                      AND d.IntTransaccion = 104
                ) THEN 'FACTURADO' ELSE 'NO FACTURADO' END AS EstadoFactura,
                (
                    SELECT TOP 1 tdoc.IntDocumento
                    FROM TblDetalleDocumentos tdoc
                    WHERE tdoc.IntDocRefD   = t.IntPedido
                      AND tdoc.StrProducto  = ttd.StrProducto
                      AND tdoc.IntTransaccion = 104
                ) AS NFactura
            ",
            )
            ->groupBy('t.IntTransaccion', 't.IntDocumento', 't.IntPedido', 'tc.StrNombre', 'tv.StrNombre', 'ttd.StrProducto', 't.StrObservaciones', 't.IntPeriodo', 't.IntAno', 't.DatFecha', 'tv.StrIdVendedor')
            ->orderBy('t.IntAno', 'DESC')
            ->orderBy('t.IntPeriodo', 'DESC')
            ->orderBy('t.IntDocumento', 'DESC')
            ->get();

        return $rows->map(fn($r) => (array) $r)->toArray();
    }*/

    // VERSIÓN SIMPLIFICADA SOLO PARA VENTAS (sin detalle de luminaria, sin joins extra, solo filtros básicos y rango de fechas). Útil para asignar O.T a producción desde ventas.
    public function searchOrdersVentas(array $filters)
    {
        $ano = isset($filters['ano']) && (int) $filters['ano'] > 0 ? (int) $filters['ano'] : (int) now()->year;
        $mes = isset($filters['mes']) && (int) $filters['mes'] >= 1 && (int) $filters['mes'] <= 12 ? (int) $filters['mes'] : (int) now()->month;

        // Parseo robusto del datepicker (prioriza dd/mm/aaaa)
        $parse = function (?string $s) {
            if (!$s) {
                return null;
            }
            foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'Ymd'] as $fmt) {
                try {
                    return \Carbon\Carbon::createFromFormat($fmt, $s);
                } catch (\Throwable $e) {
                }
            }
            try {
                return \Carbon\Carbon::parse($s);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $start = !empty($filters['start']) ? $parse($filters['start'])?->startOfDay() : null;
        $end = !empty($filters['end']) ? $parse($filters['end'])?->endOfDay() : null;
        if ($start && $end && $end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $q = \DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->join('TblVendedores as tv', 't.StrDVendedor', '=', 'tv.StrIdVendedor')
            ->leftJoin('TblDetalleDocumentos as d', function ($join) {
                $join->on('d.IntDocRefD', '=', 't.IntDocumento')->where('d.IntTransaccion', 104);
            })
            ->where('t.IntTransaccion', 109)
            ->where('t.IntEstado', 0);

        // Filtro por rango de fechas usando DatFecha
        if ($start || $end) {
            // Hay rango → traducir a (Año, Mes)
            $sY = $start?->year;
            $sM = $start?->month;
            $eY = $end?->year;
            $eM = $end?->month;

            // Si falta alguno, completa con el otro extremo
            if (!$start && $end) {
                $sY = $eY;
                $sM = 1;
            } // desde enero del año fin
            if ($start && !$end) {
                $eY = $sY;
                $eM = 12;
            } // hasta dic del año inicio

            $q->where(function ($w) use ($sY, $sM, $eY, $eM) {
                if ($sY === $eY) {
                    // Mismo año
                    $w->where('t.IntAno', $sY)->whereBetween('t.IntPeriodo', [$sM, $eM]);
                } else {
                    // Año inicial (mes >= m1)
                    $w->where(function ($w1) use ($sY, $sM) {
                        $w1->where('t.IntAno', $sY)->where('t.IntPeriodo', '>=', $sM);
                    })
                        // Años intermedios completos
                        ->orWhere(function ($w2) use ($sY, $eY) {
                            if ($sY + 1 <= $eY - 1) {
                                $w2->whereBetween('t.IntAno', [$sY + 1, $eY - 1]);
                            } else {
                                // No hay años intermedios; evita condición vacía
                                $w2->whereRaw('1=0');
                            }
                        })
                        // Año final (mes <= m2)
                        ->orWhere(function ($w3) use ($eY, $eM) {
                            $w3->where('t.IntAno', $eY)->where('t.IntPeriodo', '<=', $eM);
                        });
                }
            });
        } else {
            // Sin rango → usa los filtros "mes/año" directos
            $q->where('t.IntAno', $ano)->where('t.IntPeriodo', $mes);
        }

        // Filtros opcionales
        $q->when(!empty($filters['asesor']), fn($qq) => $qq->where('tv.StrNombre', 'like', "%{$filters['asesor']}%"))
            ->when(!empty($filters['cliente']), fn($qq) => $qq->where('tc.StrNombre', 'like', "%{$filters['cliente']}%"))
            ->when(!empty($filters['pedido']), fn($qq) => $qq->where('t.IntDocumento', $filters['pedido']));

        // Selección optimizada
        $rows = $q
            ->select(['t.IntTransaccion as TipoTransaccion', 't.IntDocumento as Pedido', 'tc.StrNombre as Tercero', 'tv.StrNombre as Vendedor', 't.StrObservaciones as Observaciones', 't.IntPeriodo as Periodo', 't.IntAno as Ano', 't.DatFecha as FechaPedido', 'tv.StrIdVendedor as VendedorUsername', \DB::raw("CASE WHEN d.IntDocumento IS NOT NULL THEN 'FACTURADO' ELSE 'NO FACTURADO' END AS EstadoFactura"), 'd.IntDocumento as NFactura'])
            ->groupBy('t.IntTransaccion', 't.IntDocumento', 'tc.StrNombre', 'tv.StrNombre', 't.StrObservaciones', 't.IntPeriodo', 't.IntAno', 't.DatFecha', 'tv.StrIdVendedor', 'd.IntDocumento', 't.StrObservaciones')
            ->orderByDesc('t.IntAno')
            ->orderByDesc('t.IntPeriodo')
            ->orderByDesc('t.IntDocumento')
            ->get();

        return $rows->map(fn($r) => (array) $r)->toArray();
    }

    // Detalle de OP (109) con posible enlace a factura (104) y su estado, filtrando por producto específico en el detalle. Si no se encuentra el documento, devuelve header vacío y lines vacío.
    public function getOrderDetailVentas(string|int $ndoc, string $producto): array
    {
        $rows = \DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->join('TblVendedores as tv', 't.StrDVendedor', '=', 'tv.StrIdVendedor')
            ->leftJoin('TblDetalleDocumentos as d', function ($join) {
                $join->on('d.IntDocRefD', '=', 't.IntDocumento')->where('d.IntTransaccion', 104);
            })
            ->where('t.IntTransaccion', 109)
            ->where('t.IntEstado', 0);

        $rows = $q
            ->select(['t.IntTransaccion as TipoTransaccion', 't.IntDocumento as Pedido', 'tc.StrNombre as Tercero', 'tv.StrNombre as Vendedor', 't.StrObservaciones as Observaciones', 't.IntPeriodo as Periodo', 't.IntAno as Ano', 't.DatFecha as FechaPedido', 'tv.StrIdVendedor as VendedorUsername', \DB::raw("CASE WHEN d.IntDocumento IS NOT NULL THEN 'FACTURADO' ELSE 'NO FACTURADO' END AS EstadoFactura"), 'd.IntDocumento as NFactura'])
            ->groupBy('t.IntTransaccion', 't.IntDocumento', 'tc.StrNombre', 'tv.StrNombre', 't.StrObservaciones', 't.IntPeriodo', 't.IntAno', 't.DatFecha', 'tv.StrIdVendedor', 'd.IntDocumento')
            ->orderByDesc('t.IntAno')
            ->orderByDesc('t.IntPeriodo')
            ->orderByDesc('t.IntDocumento')
            ->get();

        if ($rows->isEmpty()) {
            return ['header' => [], 'lines' => []];
        }

        $first = (array) $rows->first();

        $header = [
            'Pedido' => $first['Pedido'] ?? null,
            'Tercero' => $first['Tercero'] ?? null,
            'Vendedor' => $first['Vendedor'] ?? null,
            'VendedorUsername' => $first['VendedorUsername'] ?? null,
            'Periodo' => $first['Periodo'] ?? null,
            'Ano' => $first['Ano'] ?? null,
            'Observaciones' => $first['Observaciones'] ?? null,
        ];

        $lines = $rows
            ->map(function ($r) {
                return [
                    'Pedido' => $r->Pedido ?? null,
                    'EstadoFactura' => $r->EstadoFactura ?? null,
                    'NFactura' => $r->NFactura ?? null,
                ];
            })
            ->values()
            ->all();

        return ['header' => $header, 'lines' => $lines];
    }

    // Detalle de ficha de producción (141) con posible enlace a OP padre (140) y sus insumos desde TblEnsamble. Si se pasa producto, filtra solo ese insumo.
    public function getOrderDetailFichaProducto(string|int $ndoc, ?string $producto = null): array
    {
        $conn = \DB::connection('sqlsrv');

        // === HEADER (mismo que antes) ===
        $headerRow = $conn
            ->table('TblDocumentos as t')
            ->leftJoin('TblDocumentos as p', function ($j) {
                $j->on('p.IntDocumento', '=', 't.IntPedido')->where('p.IntTransaccion', 140)->where('p.IntEstado', 0);
            })
            ->leftJoin('TblTerceros as ptc', 'p.StrTercero', '=', 'ptc.StrIdTercero')
            ->leftJoin('TblVendedores as ptv', 'p.StrDVendedor', '=', 'ptv.StrIdVendedor')
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->join('TblVendedores as tv', 't.StrDVendedor', '=', 'tv.StrIdVendedor')
            ->where('t.IntTransaccion', 141)
            ->where('t.IntDocumento', $ndoc)
            ->where('t.IntEstado', 0)
            ->selectRaw(
                "
            t.DatFecha as FechaFicha,
            t.IntDocumento as FichaDocumento,
            t.IntPedido as PedidoAsociadoFicha,
            tc.StrNombre as ClienteFicha,
            tv.StrNombre as VendedorFicha,
            p.IntDocumento as EnsambleDocumento,
            p.IntPedido as EnsamblePedido,
            ptc.StrNombre as ClienteEnsamble,
            ptv.StrNombre as VendedorEnsamble,
            p.DatFecha as FechaEnsamble
        ",
            )
            ->first();

        if (!$headerRow) {
            return ['header' => [], 'lines' => []];
        }

        // === LÍNEAS (insumos desde TblEnsamble) ===
        $lines = [];

        if ($producto) {
            $lines = $conn
                ->table('TblEnsamble')
                ->where('StrProductoPPal', $producto)
                ->select(['StrProductoSecundario as Producto', 'IntCantidad as Cantidad'])
                ->orderBy('StrProductoSecundario')
                ->get()
                ->map(
                    fn($r) => [
                        'Producto' => $r->Producto,
                        'Cantidad' => (float) $r->Cantidad,
                    ],
                )
                ->values()
                ->all();
        }

        // === HEADER ===
        $hdr = [
            'FechaFicha' => $headerRow->FechaFicha ?? null,
            'FichaDocumento' => $headerRow->FichaDocumento,
            'PedidoAsociadoFicha' => $headerRow->PedidoAsociadoFicha,
            'ClienteFicha' => $headerRow->ClienteFicha,
            'VendedorFicha' => $headerRow->VendedorFicha,
            'EnsambleDocumento' => $headerRow->EnsambleDocumento,
            'EnsamblePedido' => $headerRow->EnsamblePedido,
            'ClienteEnsamble' => $headerRow->ClienteEnsamble,
            'VendedorEnsamble' => $headerRow->VendedorEnsamble,
            'FechaEnsamble' => $headerRow->FechaEnsamble?->format('Y-m-d') ?? (string) $headerRow->FechaEnsamble,
        ];

        return ['header' => $hdr, 'lines' => $lines];
    }

    // Traer datos de pedidos (O.T) para asignar a producción, excluyendo los ya asignados (MySQL)
    public function getOrdenesTrabajo($search = null, $vendedor = null)
    {
        $documentosAsignados = \DB::connection('mysql')->table('work_orders')->pluck('n_documento')->toArray();

        $query = \DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->join('TblVendedores as tv', 't.StrDVendedor', '=', 'tv.StrIdVendedor')

            // ✅ UNA factura por pedido
            ->leftJoin(
                \DB::raw("
                (
                    SELECT
                        IntDocRefD,
                        MAX(IntDocumento) AS IntDocumento
                    FROM TblDetalleDocumentos
                    WHERE IntTransaccion = 104
                    GROUP BY IntDocRefD
                ) d
            "),
                'd.IntDocRefD',
                '=',
                't.IntDocumento',
            )

            // 🔴 Excluir ya asignados
            ->when(!empty($documentosAsignados), function ($q) use ($documentosAsignados) {
                $q->whereNotIn('t.IntDocumento', $documentosAsignados);
            })


            // 🔒 FILTRO AUTOMÁTICO POR ASESOR LOGUEADO
            ->when($vendedor, function ($q) use ($vendedor) {
                $q->where('tv.StrIdVendedor', $vendedor);
            })

            // 🔍 Búsqueda
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('t.IntDocumento', 'like', "%{$search}%")
                        ->orWhere('tc.StrNombre', 'like', "%{$search}%")
                        ->orWhere('tv.StrNombre', 'like', "%{$search}%");
                });
            })

            ->where('t.IntTransaccion', 109)
            ->where('t.IntEstado', 0)

            ->select([
                't.IntDocumento as n_documento',
                'tc.StrNombre as tercero',
                'tv.StrNombre as vendedor',
                'tv.StrIdVendedor as vendedor_username',
                't.IntPeriodo as periodo',
                't.IntAno as ano',
                'd.IntDocumento as n_factura',
                \DB::raw("
                CASE
                    WHEN d.IntDocumento IS NOT NULL
                    THEN 'FACTURADO'
                    ELSE 'NO FACTURADO'
                END AS estado
            "),
            ])

            ->orderByDesc('t.IntAno')
            ->orderByDesc('t.IntPeriodo')
            ->orderByDesc('t.IntDocumento');

        // ✅ AQUÍ ESTABA EL PROBLEMA
        return $search
            ? $query->get() // 🔍 sin paginar
            : $query->paginate(50); // 📄 paginado
    }
}