<?php 

namespace App\Repository\HGI;

use Illuminate\Support\Facades\DB;

class MaterialRepository
{
    public function getAllMaterials($lastId, $limit)
    {
         $limit = (int) $limit;

        return DB::connection('sqlsrv')->select("
            SELECT TOP $limit
                p.StrIdProducto AS codigo,
                p.StrDescripcion AS nombre,
                p.StrParam1 AS ubicacion,
                img.Imagen,

                ISNULL(inv.saldo_inventario,0) AS saldo_inventario,
                ISNULL(res.saldo_reservado,0) AS saldo_reservado

            FROM TblProductos p WITH (NOLOCK)

            LEFT JOIN TblImagenes img WITH (NOLOCK)
                ON img.StrIdCodigo = p.StrIdProducto
                AND img.StrTabla = 'TblProductos'

            -- INVENTARIO PRECALCULADO
            LEFT JOIN (
                SELECT 
                    s.StrProducto,
                    SUM(s.IntSaldoI + s.IntEntradas - s.IntSalidas - s.IntSalidasT) AS saldo_inventario
                FROM QrySaldosInv1 s WITH (NOLOCK)
                WHERE s.IntBodega = '01'
                AND s.IntAno = YEAR(GETDATE())
                AND s.IntPeriodo = MONTH(GETDATE())
                AND s.IntEmpresa = '01'
                GROUP BY s.StrProducto
            ) inv ON inv.StrProducto = p.StrIdProducto

            -- RESERVAS PRECALCULADAS
            LEFT JOIN (
                SELECT 
                    sp.StrProducto,
                    SUM(CASE WHEN sp.IntSaldoFinal > 0 THEN sp.IntSaldoFinal ELSE 0 END) AS saldo_reservado
                FROM QrySaldoPedidos sp WITH (NOLOCK)
                WHERE sp.IntTransaccion = 109
                AND sp.IntPeriodo = MONTH(GETDATE())
                AND sp.IntAno = YEAR(GETDATE())
                GROUP BY sp.StrProducto
            ) res ON res.StrProducto = p.StrIdProducto

            WHERE p.StrIdProducto > ?
            ORDER BY p.StrIdProducto
        ", [$lastId]);
    }
}