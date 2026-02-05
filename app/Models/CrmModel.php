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
        $offset = (int) $offset;
        $limit = (int) $limit;

        $where = '';
        $params = [];

        /* ================= FECHAS ================= */
        if (!empty($filters['start'])) {
            $where .= ' AND CONVERT(date, t.Fecha) >= CONVERT(date, ?) ';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $where .= ' AND CONVERT(date, t.Fecha) <= CONVERT(date, ?) ';
            $params[] = $filters['end'];
        }

        /* ================= ASESORES (UNO O VARIOS) ================= */
        if (!empty($filters['asesores']) && is_array($filters['asesores'])) {
            $in = implode(',', array_fill(0, count($filters['asesores']), '?'));
            $where .= " AND t.Usuario IN ($in) ";
            foreach ($filters['asesores'] as $a) {
                $params[] = $a;
            }
        }

        /* ================= SEARCH ================= */
        if (!empty($filters['search'])) {
            $where .= "
            AND (
                t.IdOportunidad LIKE '%' + ? + '%'
                OR t.Nombre LIKE '%' + ? + '%'
                OR a.StrDescripcion LIKE '%' + ? + '%'
                OR c.StrObservaciones LIKE '%' + ? + '%'
            )
        ";
            $params[] = $filters['search'];
            $params[] = $filters['search'];
            $params[] = $filters['search'];
            $params[] = $filters['search'];
        }

        /* ================= PAGINACIÓN ================= */
        $params[] = $offset;
        $params[] = $limit;


        /* ================= FILTROS Sector ================= */
        if (!empty($filters['sector'])) {
            $where .= ' AND t.Parametro1 = ? ';
            $params[] = $filters['sector'];
        }

        //================= FILTROS Estado ================= */
        if (!empty($filters['estado'])) {
            $where .= ' AND t.Estado = ? ';
            $params[] = $filters['estado'];
        }

        //================= FILTROS Resultado ================= */
        if (!empty($filters['resultado'])) {
            $where .= ' AND t.Resultado = ? ';
            $params[] = $filters['resultado'];
        }

        //================= FILTROS Fuente ================= */
        if (!empty($filters['fuente'])) {
            $where .= ' AND t.Fuente = ? ';
            $params[] = $filters['fuente'];
        }

        return DB::connection('sqlsrv')->select(
            "
        SELECT
            CONVERT(date, t.Fecha)       AS FechaRegistro,
            CONVERT(date, t.FechaCierre) AS FechaCierre,

            t.IdOportunidad AS Identificador,
            t.Nombre        AS Cliente,
            t.Usuario       AS Asesor,

            t.Direccion,
            t.Telefono,
            t.StrCelular,
            t.Email,
            t.Contacto,

            u.StrNombre    AS NombreAsesor,

            e.StrDescripcion    AS EstadoOportunidad,
            crm1.StrDescripcion AS Etapa,
            f.Descripcion       AS FuenteOportunidad,
            r.StrDescripcion    AS ResultadoOportunidad,
            ca.StrDescripcion   AS CausaOportunidad,

            trp1.StrDescripcion AS SectorProyecto,
            crm2.StrDescripcion AS CiudadProyecto,
            trp2.StrDescripcion AS Gremio,

            com.StrDescripcion  AS ProvActual,
            com2.StrDescripcion AS ProvCompetidor,

            t.Interes     AS InteresProspecto,
            t.Presupuesto AS Presupuesto,

            a.StrDescripcion           AS Actividad,
            CONVERT(date, c.DatInicio) AS FechaActividad,
            CONVERT(time, c.DatInicio) AS HoraInicialActividad,
            c.StrObservaciones         AS Observacion,
            c.IntEstado                AS EstadoActividad

        FROM TblOportunidades t
        INNER JOIN TblCitas c               ON t.IdOportunidad = c.Oportunidad
        INNER JOIN TblActividades a         ON c.StrActividad = a.StrIdActividad
        INNER JOIN TblEstado e              ON t.Estado = e.IntIdEstado
        INNER JOIN TblCrmParametro1 crm1    ON t.StrCrmParametro1 = crm1.StrIdParametro
        INNER JOIN TblCrmParametro2 crm2    ON t.StrCrmParametro2 = crm2.StrIdParametro
        INNER JOIN TblFuentes f             ON t.Fuente = f.IdFuente
        INNER JOIN TblResultados r          ON t.Resultado = r.IdResultado
        INNER JOIN TblCausas ca             ON t.Causa = ca.StrIdCausa
        INNER JOIN TblTerParametro1 trp1    ON t.Parametro1 = trp1.StrIdParametro
        INNER JOIN TblTerParametro2 trp2    ON t.Parametro2 = trp2.StrIdParametro
        LEFT JOIN TblCompetidores com       ON t.PrActual = com.IdCompetidor
        LEFT JOIN TblCompetidores com2      ON t.PrCompetidor = com2.IdCompetidor
        INNER JOIN TblUsuarios u            ON t.Usuario = u.StrIdUsuario

        WHERE 1 = 1
        $where

        ORDER BY t.Fecha DESC, c.DatInicio DESC
        OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY
        ",
            $params,
        );
    }

    public static function contarSeguimiento(array $filters): int
    {
        $where  = '';
        $params = [];

        if (!empty($filters['start'])) {
            $where .= ' AND CONVERT(date, t.Fecha) >= CONVERT(date, ?) ';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $where .= ' AND CONVERT(date, t.Fecha) <= CONVERT(date, ?) ';
            $params[] = $filters['end'];
        }

        if (!empty($filters['asesores']) && is_array($filters['asesores'])) {
            $in = implode(',', array_fill(0, count($filters['asesores']), '?'));
            $where .= " AND t.Usuario IN ($in) ";
            foreach ($filters['asesores'] as $a) {
                $params[] = $a;
            }
        }


        /* ================= FILTROS Sector ================= */
        if (!empty($filters['sector'])) {
            $where .= ' AND t.Parametro1 = ? ';
            $params[] = $filters['sector'];
        }

        //================= FILTROS Estado ================= */
        if (!empty($filters['estado'])) {
            $where .= ' AND t.Estado = ? ';
            $params[] = $filters['estado'];
        }

        //================= FILTROS Resultado ================= */
        if (!empty($filters['resultado'])) {
            $where .= ' AND t.Resultado = ? ';
            $params[] = $filters['resultado'];
        }

        //================= FILTROS Fuente ================= */
        if (!empty($filters['fuente'])) {
            $where .= ' AND t.Fuente = ? ';
            $params[] = $filters['fuente'];
        }

        // SEARCH (opcional)

        if (!empty($filters['search'])) {
            $where .= "
                AND (
                    t.IdOportunidad LIKE '%' + ? + '%'
                    OR t.Nombre LIKE '%' + ? + '%'
                    OR a.StrDescripcion LIKE '%' + ? + '%'
                )
            ";
            $params[] = $filters['search'];
            $params[] = $filters['search'];
            $params[] = $filters['search'];
        }

        $row = DB::connection('sqlsrv')->selectOne(
            "
            SELECT COUNT(DISTINCT t.IdOportunidad) AS total
            FROM TblOportunidades t
            INNER JOIN TblCitas c ON t.IdOportunidad = c.Oportunidad
            INNER JOIN TblActividades a ON c.StrActividad = a.StrIdActividad
            INNER JOIN TblEstado e              ON t.Estado = e.IntIdEstado
            INNER JOIN TblCrmParametro1 crm1    ON t.StrCrmParametro1 = crm1.StrIdParametro
            INNER JOIN TblCrmParametro2 crm2    ON t.StrCrmParametro2 = crm2.StrIdParametro
            INNER JOIN TblFuentes f             ON t.Fuente = f.IdFuente
            INNER JOIN TblResultados r          ON t.Resultado = r.IdResultado
            INNER JOIN TblCausas ca             ON t.Causa = ca.StrIdCausa
            INNER JOIN TblTerParametro1 trp1    ON t.Parametro1 = trp1.StrIdParametro
            INNER JOIN TblTerParametro2 trp2    ON t.Parametro2 = trp2.StrIdParametro
            LEFT JOIN TblCompetidores com       ON t.PrActual = com.IdCompetidor
            LEFT JOIN TblCompetidores com2      ON t.PrCompetidor = com2.IdCompetidor
            INNER JOIN TblUsuarios u            ON t.Usuario = u.StrIdUsuario
            WHERE 1 = 1
            $where
            ",
            $params
        );

        return (int) $row->total;
    }


    // Totales globales – Seguimiento CRM (Oportunidades)
    public static function totalesOportunidades(array $filters): array
    {
        $where  = '';
        $params = [];

        // FECHAS
        if (!empty($filters['start'])) {
            $where .= ' AND CONVERT(date, t.Fecha) >= ? ';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $where .= ' AND CONVERT(date, t.Fecha) <= ? ';
            $params[] = $filters['end'];
        }

        // ASESORES
        if (!empty($filters['asesores']) && is_array($filters['asesores'])) {
            $in = implode(',', array_fill(0, count($filters['asesores']), '?'));
            $where .= " AND t.Usuario IN ($in) ";
            foreach ($filters['asesores'] as $a) {
                $params[] = $a;
            }
        }


        /* ================= FILTROS Sector ================= */
        if (!empty($filters['sector'])) {
            $where .= ' AND t.Parametro1 = ? ';
            $params[] = $filters['sector'];
        }

        //================= FILTROS Estado ================= */
        if (!empty($filters['estado'])) {
            $where .= ' AND t.Estado = ? ';
            $params[] = $filters['estado'];
        }

        //================= FILTROS Resultado ================= */
        if (!empty($filters['resultado'])) {
            $where .= ' AND t.Resultado = ? ';
            $params[] = $filters['resultado'];
        }

        //================= FILTROS Fuente ================= */
        if (!empty($filters['fuente'])) {
            $where .= ' AND t.Fuente = ? ';
            $params[] = $filters['fuente'];
        }


        // SEARCH (opcional)
        if (!empty($filters['search'])) {
            $where .= "
                AND (
                    t.IdOportunidad LIKE '%' + ? + '%'
                    OR t.Nombre LIKE '%' + ? + '%'
                )
            ";
            $params[] = $filters['search'];
            $params[] = $filters['search'];
        }

        return (array) DB::connection('sqlsrv')->selectOne(
            "
            SELECT
                COUNT(DISTINCT t.IdOportunidad) AS total_oportunidades,
                COUNT(c.Oportunidad)            AS total_actividades
            FROM TblOportunidades t
            INNER JOIN TblCitas c ON t.IdOportunidad = c.Oportunidad
            WHERE 1 = 1
            $where
            ",
            $params
        );
    }


    // Totales por asesor – Seguimiento CRM
    public static function totalesOportunidadesPorAsesor(array $filters): array
    {
        $where  = '';
        $params = [];

        // FECHAS
        if (!empty($filters['start'])) {
            $where .= ' AND CONVERT(date, t.Fecha) >= ? ';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $where .= ' AND CONVERT(date, t.Fecha) <= ? ';
            $params[] = $filters['end'];
        }

        // ASESORES
        if (!empty($filters['asesores']) && is_array($filters['asesores'])) {
            $in = implode(',', array_fill(0, count($filters['asesores']), '?'));
            $where .= " AND t.Usuario IN ($in) ";
            foreach ($filters['asesores'] as $a) {
                $params[] = $a;
            }
        }


        /* ================= FILTROS Sector ================= */
        if (!empty($filters['sector'])) {
            $where .= ' AND t.Parametro1 = ? ';
            $params[] = $filters['sector'];
        }

        //================= FILTROS Estado ================= */
        if (!empty($filters['estado'])) {
            $where .= ' AND t.Estado = ? ';
            $params[] = $filters['estado'];
        }

        //================= FILTROS Resultado ================= */
        if (!empty($filters['resultado'])) {
            $where .= ' AND t.Resultado = ? ';
            $params[] = $filters['resultado'];
        }

        //================= FILTROS Fuente ================= */
        if (!empty($filters['fuente'])) {
            $where .= ' AND t.Fuente = ? ';
            $params[] = $filters['fuente'];
        }


        return DB::connection('sqlsrv')->select(
            "
            SELECT
                u.StrNombre AS asesor,
                COUNT(DISTINCT t.IdOportunidad) AS oportunidades,
                COUNT(c.Oportunidad)            AS actividades
            FROM TblOportunidades t
            INNER JOIN TblCitas c   ON t.IdOportunidad = c.Oportunidad
            INNER JOIN TblUsuarios u ON u.StrIdUsuario = t.Usuario
            WHERE 1 = 1
            $where
            GROUP BY u.StrNombre
            ORDER BY u.StrNombre
            ",
            $params
        );
    }



    /* ===== LISTAR EVENTOS / VISITAS CRM ===== */
    public static function obtenerEventos(int $offset, int $limit, array $filters): array
    {
        $offset = (int) $offset;
        $limit = (int) $limit;

        $where = '';
        $params = [];

        // FECHAS
        if (!empty($filters['start'])) {
            $where .= ' AND CONVERT(date, tv.DatFechaInicio) >= CONVERT(date, ?) ';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $where .= ' AND CONVERT(date, tv.DatFechaInicio) <= CONVERT(date, ?) ';
            $params[] = $filters['end'];
        }

        // 🔥 ASESORES (UNO O VARIOS)
        if (!empty($filters['asesores']) && is_array($filters['asesores'])) {
            $in = implode(',', array_fill(0, count($filters['asesores']), '?'));
            $where .= " AND tv.StrRecibio IN ($in) ";
            foreach ($filters['asesores'] as $a) {
                $params[] = $a;
            }
        }

        // TIPO EVENTO
        if (!empty($filters['tipoEvento'])) {
            $where .= ' AND tv.StrTipoEvento = ? ';
            $params[] = $filters['tipoEvento'];
        }

        // SEARCH
        if (!empty($filters['search'])) {
            $where .= " AND (
            tc.StrNombre LIKE '%' + ? + '%' OR
            a.StrDescripcion LIKE '%' + ? + '%' OR
            tdv.StrDetalle LIKE '%' + ? + '%'
        ) ";
            $params[] = $filters['search'];
            $params[] = $filters['search'];
            $params[] = $filters['search'];
        }

        return DB::connection('sqlsrv')->select(
            "
        SELECT
            tv.IntIdEvento,
            CONVERT(date, tv.DatFechaInicio) AS FechaRegistro,
            CONVERT(date, tv.DatFecha) AS FechaRegistroDocumento,

            tv.StrTercero AS NitTercero,
            tc.StrNombre  AS NombreTercero,

            tv.StrRecibio AS UsuarioAsesor,
            u.StrNombre   AS NombreAsesor,

            tr.StrDescripcion AS ReferenciaCliente,
            te.StrDescripcion AS TipoEvento,
            tv.StrDescripcion AS Observaciones,

            CONVERT(date, tdv.DatFecha) AS FechaRegistroActividad,
            a.StrDescripcion AS TipoActividad,
            tdv.StrDetalle   AS DetalleActividad,

            (
                SELECT COUNT(*)
                FROM TblGestionDocumentos gd
                WHERE gd.IntEvento = tv.IntIdEvento
            ) AS cantidad_adjuntos

        FROM TblEventos tv
        INNER JOIN TblDetalleEventos tdv ON tv.IntIdEvento = tdv.IntEvento
        INNER JOIN TblTerceros tc ON tv.StrTercero = tc.StrIdTercero
        INNER JOIN TblReferencias tr ON tv.StrReferencia = tr.StrIdReferencia
        INNER JOIN TblActividades a ON tdv.StrActividad = a.StrIdActividad
        INNER JOIN TblTiposEvento te ON tv.StrTipoEvento = te.StrIdTipo
        INNER JOIN TblUsuarios u ON u.StrIdUsuario = tv.StrRecibio

        WHERE 1 = 1 AND tv.StrTipoEvento IN (10,11,14,21,22,23)
        $where



        ORDER BY tv.DatFechaInicio DESC, tv.DatFecha DESC
        OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY
        ",
            $params,
        );
    }

    /* Contar Eventos / Visitas CRM */
    public static function contarEventos(array $filters): int
    {
        $where = '';
        $params = [];

        if ($filters['start']) {
            $where .= ' AND CONVERT(date, tv.DatFechaInicio) >= ? ';
            $params[] = $filters['start'];
        }

        if ($filters['end']) {
            $where .= ' AND CONVERT(date, tv.DatFechaInicio) <= ? ';
            $params[] = $filters['end'];
        }

        if (!empty($filters['asesores'])) {
            $placeholders = implode(',', array_fill(0, count($filters['asesores']), '?'));
            $where .= " AND tv.StrRecibio IN ($placeholders) ";
            foreach ($filters['asesores'] as $a) {
                $params[] = $a;
            }
        }

        if ($filters['tipoEvento']) {
            $where .= ' AND tv.StrTipoEvento = ? ';
            $params[] = $filters['tipoEvento'];
        }

        if ($filters['search']) {
            $where .= " AND (
            tc.StrNombre LIKE '%' + ? + '%' OR
            a.StrDescripcion LIKE '%' + ? + '%' OR
            tdv.StrDetalle LIKE '%' + ? + '%'
        )";
            $params[] = $filters['search'];
            $params[] = $filters['search'];
            $params[] = $filters['search'];
        }

        $row = DB::connection('sqlsrv')->selectOne(
            "
        SELECT COUNT(*) AS total
        FROM TblEventos tv
        INNER JOIN TblDetalleEventos tdv ON tv.IntIdEvento = tdv.IntEvento
        INNER JOIN TblTerceros tc ON tv.StrTercero = tc.StrIdTercero
        INNER JOIN TblActividades a ON tdv.StrActividad = a.StrIdActividad
        WHERE 1=1 AND tv.StrTipoEvento IN (10,11,21,22,23)
        $where
    ",
            $params,
        );

        return (int) $row->total;
    }

    // Totales Eventos / Visitas CRM
    public static function totalesEventos(array $filters): array
    {
        $where = '';
        $params = [];

        // FECHAS
        if (!empty($filters['start'])) {
            $where .= ' AND CONVERT(date, tv.DatFechaInicio) >= ? ';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $where .= ' AND CONVERT(date, tv.DatFechaInicio) <= ? ';
            $params[] = $filters['end'];
        }

        // ASESORES
        if (!empty($filters['asesores']) && is_array($filters['asesores'])) {
            $in = implode(',', array_fill(0, count($filters['asesores']), '?'));
            $where .= " AND tv.StrRecibio IN ($in) ";
            foreach ($filters['asesores'] as $a) {
                $params[] = $a;
            }
        }

        // TIPO EVENTO (UNO SOLO, SELECT)
        if (!empty($filters['tipoEvento'])) {
            $where .= ' AND tv.StrTipoEvento = ? ';
            $params[] = $filters['tipoEvento'];
        }

        return (array) DB::connection('sqlsrv')->selectOne(
            "
            SELECT
                COUNT(DISTINCT tv.IntIdEvento) AS total_eventos,
                COUNT(*)                       AS total_actividades
            FROM TblEventos tv
            INNER JOIN TblDetalleEventos tdv ON tv.IntIdEvento = tdv.IntEvento
            WHERE tv.StrTipoEvento IN (10,11,21,22,23)
            $where
            ",
            $params,
        );
    }

    // Totales por Asesor - Eventos / Visitas CRM
    public static function totalesPorAsesor(array $filters): array
    {
        $where = '';
        $params = [];

        // FECHAS
        if (!empty($filters['start'])) {
            $where .= ' AND CONVERT(date, tv.DatFechaInicio) >= ? ';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $where .= ' AND CONVERT(date, tv.DatFechaInicio) <= ? ';
            $params[] = $filters['end'];
        }

        // ASESORES
        if (!empty($filters['asesores'])) {
            $in = implode(',', array_fill(0, count($filters['asesores']), '?'));
            $where .= " AND tv.StrRecibio IN ($in) ";
            foreach ($filters['asesores'] as $a) {
                $params[] = $a;
            }
        }

        return DB::connection('sqlsrv')->select(
            "
            SELECT
                u.StrNombre AS asesor,
                COUNT(DISTINCT tv.IntIdEvento) AS eventos,
                COUNT(tdv.IntEvento) AS actividades
            FROM TblEventos tv
            INNER JOIN TblDetalleEventos tdv ON tv.IntIdEvento = tdv.IntEvento
            INNER JOIN TblUsuarios u ON u.StrIdUsuario = tv.StrRecibio
            WHERE tv.StrTipoEvento IN (10,11,21,22,23)
            $where
            GROUP BY u.StrNombre
            ORDER BY u.StrNombre
            ",
            $params,
        );
    }

    /* ===== KPIs ===== */
    public static function kpiResumen(array $filters)
    {
        return DB::connection('sqlsrv')->selectOne(
            "
            SELECT
                COUNT(DISTINCT t.IdOportunidad) AS total_oportunidades,

                SUM(CASE WHEN t.FechaCierre IS NULL THEN 1 ELSE 0 END) AS abiertas,

                SUM(CASE WHEN t.FechaCierre IS NOT NULL THEN 1 ELSE 0 END) AS cerradas,

                SUM(
                    CASE
                        WHEN t.FechaCierre IS NULL
                        AND cAtraso.tiene_atraso = 1
                        THEN 1 ELSE 0
                    END
                ) AS abiertas_con_atraso

            FROM TblOportunidades t

            LEFT JOIN (
                SELECT
                    c.Oportunidad,
                    1 AS tiene_atraso
                FROM TblCitas c
                WHERE
                    c.IntEstado <> 1
                    AND c.DatInicio < GETDATE()
                GROUP BY c.Oportunidad
            ) cAtraso
                ON cAtraso.Oportunidad = t.IdOportunidad

            WHERE
                (? IS NULL OR t.Fecha >= CAST(? AS DATE))
                AND (? IS NULL OR t.Fecha <= CAST(? AS DATE))
                AND (? IS NULL OR t.Usuario = ?)
        ",
            [$filters['start'], $filters['start'], $filters['end'], $filters['end'], $filters['asesor'], $filters['asesor']],
        );
    }

    public static function kpiPorActividad(array $filters): array
    {
        return DB::connection('sqlsrv')->select(
            "
            SELECT
                a.StrDescripcion AS actividad,
                COUNT(DISTINCT t.IdOportunidad) AS oportunidades,
                SUM(
                    CASE
                        WHEN c.IntEstado <> 1
                        AND c.DatInicio < GETDATE()
                        THEN 1 ELSE 0
                    END
                ) AS atrasadas
            FROM TblOportunidades t
            INNER JOIN TblCitas c
                ON c.Oportunidad = t.IdOportunidad
            INNER JOIN TblActividades a
                ON a.StrIdActividad = c.StrActividad
            WHERE
                t.FechaCierre IS NULL
                AND (? IS NULL OR t.Fecha >= CAST(? AS DATE))
                AND (? IS NULL OR t.Fecha <= CAST(? AS DATE))
                AND (? IS NULL OR t.Usuario = ?)
            GROUP BY a.StrDescripcion
            ORDER BY atrasadas DESC
        ",
            [$filters['start'], $filters['start'], $filters['end'], $filters['end'], $filters['asesor'], $filters['asesor']],
        );
    }

    public static function kpiPorAsesor(array $filters): array
    {
        return DB::connection('sqlsrv')->select(
            "
            SELECT
                t.Usuario AS asesor,
                COUNT(DISTINCT t.IdOportunidad) AS total,
                SUM(CASE WHEN t.FechaCierre IS NULL THEN 1 ELSE 0 END) AS abiertas,
                SUM(CASE WHEN t.FechaCierre IS NOT NULL THEN 1 ELSE 0 END) AS cerradas
            FROM TblOportunidades t
            WHERE
                (? IS NULL OR t.Fecha >= CAST(? AS DATE))
                AND (? IS NULL OR t.Fecha <= CAST(? AS DATE))
            GROUP BY t.Usuario
            ORDER BY abiertas DESC
        ",
            [$filters['start'], $filters['start'], $filters['end'], $filters['end']],
        );
    }

    public static function kpiPendientesDetalle(array $filters): array
    {
        return DB::connection('sqlsrv')->select(
            "
            SELECT
                t.IdOportunidad,
                t.Nombre AS cliente,
                t.Usuario AS asesor,
                a.StrDescripcion AS actividad,
                c.DatInicio AS fecha,
                c.StrObservaciones
            FROM TblOportunidades t
            INNER JOIN TblCitas c ON c.Oportunidad = t.IdOportunidad
            INNER JOIN TblActividades a ON c.StrActividad = a.StrIdActividad
            WHERE
                t.FechaCierre IS NULL
                AND c.IntEstado <> 1
                AND c.DatInicio < GETDATE()
                AND (? IS NULL OR t.Usuario = ?)
            ORDER BY c.DatInicio ASC
        ",
            [$filters['asesor'], $filters['asesor']],
        );
    }

    public static function kpiCotizacionSinSeguimiento(int $dias = 7): array
    {
        return DB::connection('sqlsrv')->select(
            "
            SELECT
                t.IdOportunidad,
                t.Nombre AS cliente,
                t.Usuario AS asesor,
                MAX(c.DatInicio) AS ultimaActividad
            FROM TblOportunidades t
            INNER JOIN TblCitas c ON t.IdOportunidad = c.Oportunidad
            WHERE
                c.StrActividad = 'COTIZACION'
            GROUP BY t.IdOportunidad, t.Nombre, t.Usuario
            HAVING MAX(c.DatInicio) < DATEADD(DAY, -?, GETDATE())
            ORDER BY ultimaActividad ASC
        ",
            [$dias],
        );
    }
}
