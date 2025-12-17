<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CrmModel extends Model
{
    //
    protected $connection = 'sqlsrv';
    public $timestamps = false;

    /**
     * Informe de seguimiento CRM
     */
    public static function obtenerSeguimiento(int $offset, int $limit, array $filters): array
    {
        return DB::connection('sqlsrv')->select(
            "
        SELECT
            CONVERT(date, t.Fecha) AS FechaRegistro,
            CONVERT(date, t.FechaCierre) AS FechaCierre,
            t.IdOportunidad AS Identificador,
            t.Nombre AS Cliente,
            t.Direccion,
            t.Telefono,
            t.StrCelular,
            t.Email,
            t.Contacto,
            t.Usuario AS Asesor,
            a.StrDescripcion AS Actividad,
            CONVERT(date, c.DatInicio) AS FechaActividad,
            CONVERT(time, c.DatInicio) AS HoraInicialActividad,
            c.StrObservaciones AS Observacion,
            c.IntEstado AS EstadoActividad
        FROM TblOportunidades t
        INNER JOIN TblCitas c ON t.IdOportunidad = c.Oportunidad
        INNER JOIN TblActividades a ON c.StrActividad = a.StrIdActividad
        WHERE 1 = 1
            AND (? IS NULL OR t.Fecha >= CAST(? AS DATE))
            AND (? IS NULL OR t.Fecha <= CAST(? AS DATE))
            AND (? IS NULL OR t.Usuario = ?)
            AND (
                ? IS NULL OR
                t.IdOportunidad LIKE '%' + ? + '%' OR
                t.Nombre LIKE '%' + ? + '%' OR
                a.StrDescripcion LIKE '%' + ? + '%'
            )
        ORDER BY t.Fecha DESC, c.DatInicio DESC
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
    ",
            [$filters['start'], $filters['start'], $filters['end'], $filters['end'], $filters['asesor'], $filters['asesor'], $filters['search'], $filters['search'], $filters['search'], $filters['search'], $offset, $limit],
        );
    }

    public static function contarSeguimiento(array $filters): int
    {
        $row = DB::connection('sqlsrv')->selectOne(
            "
        SELECT COUNT(*) AS total
        FROM TblOportunidades t
        INNER JOIN TblCitas c ON t.IdOportunidad = c.Oportunidad
        INNER JOIN TblActividades a ON c.StrActividad = a.StrIdActividad
        WHERE 1 = 1
            AND (? IS NULL OR t.Fecha >= CAST(? AS DATE))
            AND (? IS NULL OR t.Fecha <= CAST(? AS DATE))
            AND (? IS NULL OR t.Usuario = ?)
            AND (
                ? IS NULL OR
                t.IdOportunidad LIKE '%' + ? + '%' OR
                t.Nombre LIKE '%' + ? + '%' OR
                a.StrDescripcion LIKE '%' + ? + '%'
            )
    ",
            [$filters['start'], $filters['start'], $filters['end'], $filters['end'], $filters['asesor'], $filters['asesor'], $filters['search'], $filters['search'], $filters['search'], $filters['search']],
        );

        return (int) $row->total;
    }
}
