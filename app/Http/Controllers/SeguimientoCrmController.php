<?php

namespace App\Http\Controllers;

use Carbon\Carbon;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


use App\Exports\EventosExport;
use App\Exports\OportunidadesExport;


use Maatwebsite\Excel\Facades\Excel;

// Services
use App\Services\CrmService;


use Throwable;

class SeguimientoCrmController
{
    protected CrmService $crmService;

    public function __construct(CrmService $crmService)
    {
        $this->crmService = $crmService;
    }

    public function index()
    {
        return view('crm.index');
    }

    /**
     * Display a listing of the resource.
     */
    public function data(Request $request)
    {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);

            $filters = [
                'start' => $request->get('start') ?: null,
                'end' => $request->get('end') ?: null,
                'asesores' => $request->filled('asesores') ? $request->get('asesores') : null,
                'search' => $request->get('search') ?: null,

                'sector' => $request->get('sector'),
                'estado' => $request->get('estado'),
                'resultado' => $request->get('resultado'),
                'fuente' => $request->get('fuente'),
            ];

            $user = Auth::user();

            /**
             * Seguridad real:
             * si NO es perfil global, FORZAMOS el asesor al username
             */
            if (!in_array($user->perfil_usuario_id, [1, 2, 9])) {
                $filters['asesores'] = [$user->username];
            }

            $result = $this->crmService->listCrm($page, $perPage, $filters);

            return response()->json([
                'success' => true,
                    'data' => collect($result['rows'])->map(
                        fn($r) => [
                            // 🔹 FECHAS
                            'fechaRegistro' => $r->FechaRegistro,
                            'fechaCierre' => $r->FechaCierre,

                            // 🔹 OPORTUNIDAD
                            'oportunidad' => $r->Identificador,
                            'cliente' => $r->Cliente,
                            'asesor' => $r->NombreAsesor,

                            // 🔹 ESTADO / CRM
                            'estado' => $r->EstadoOportunidad,
                            'etapa' => $r->Etapa,
                            'fuente' => $r->FuenteOportunidad,
                            'resultado' => $r->ResultadoOportunidad,
                            'causa' => $r->CausaOportunidad,

                            // 🔹 PROYECTO
                            'sector' => $r->SectorProyecto,
                            'ciudad' => $r->CiudadProyecto,
                            'gremio' => $r->Gremio,

                            // 🔹 COMPETENCIA
                            'proveedorActual' => $r->ProvActual,
                            'proveedorCompetidor' => $r->ProvCompetidor,

                            // 🔹 NEGOCIO
                            'interes' => $r->InteresProspecto,
                            'presupuesto' => $r->Presupuesto,

                            // 🔹 ACTIVIDAD
                            'actividad' => $r->Actividad,
                            'fechaActividad' => $r->FechaActividad,
                            'observacion' => $r->Observacion,

                            // 🔹 CONTACTO
                            'telefono' => $r->Telefono,
                            'celular' => $r->StrCelular,
                            'email' => $r->Email,
                            'contacto' => $r->Contacto,
                            'direccion' => $r->Direccion,
                        ],

                    ),

                'totales' => $result['totales'],
                'totales_por_asesor' => $result['totales_por_asesor'],
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $result['total'],
                    'last_page' => (int) ceil($result['total'] / $perPage),
                ],
            ]);

        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);

            $filters = [
                'start' => $request->get('start') ?: null,
                'end' => $request->get('end') ?: null,
                'asesores' => $request->filled('asesores') ? $request->get('asesores') : null,
                'search' => $request->get('search') ?: null,

                'sector' => $request->get('sector'),
                'estado' => $request->get('estado'),
                'resultado' => $request->get('resultado'),
                'fuente' => $request->get('fuente'),
            ];

            $user = Auth::user();

            /**
             * Seguridad real:
             * si NO es perfil global, FORZAMOS el asesor al username
             */
            if (!in_array($user->perfil_usuario_id, [1, 2, 9])) {
                $filters['asesores'] = [$user->username];
            }

            $result = $this->crmService->listCrm($page, $perPage, $filters);

            return response()->json([
                'success' => true,
                    'data' => collect($result['rows'])->map(
                        fn($r) => [
                            // 🔹 FECHAS
                            'fechaRegistro' => $r->FechaRegistro,
                            'fechaCierre' => $r->FechaCierre,

                            // 🔹 OPORTUNIDAD
                            'oportunidad' => $r->Identificador,
                            'cliente' => $r->Cliente,
                            'asesor' => $r->NombreAsesor,

                            // 🔹 ESTADO / CRM
                            'estado' => $r->EstadoOportunidad,
                            'etapa' => $r->Etapa,
                            'fuente' => $r->FuenteOportunidad,
                            'resultado' => $r->ResultadoOportunidad,
                            'causa' => $r->CausaOportunidad,

                            // 🔹 PROYECTO
                            'sector' => $r->SectorProyecto,
                            'ciudad' => $r->CiudadProyecto,
                            'gremio' => $r->Gremio,

                            // 🔹 COMPETENCIA
                            'proveedorActual' => $r->ProvActual,
                            'proveedorCompetidor' => $r->ProvCompetidor,

                            // 🔹 NEGOCIO
                            'interes' => $r->InteresProspecto,
                            'presupuesto' => $r->Presupuesto,

                            // 🔹 ACTIVIDAD
                            'actividad' => $r->Actividad,
                            'fechaActividad' => $r->FechaActividad,
                            'observacion' => $r->Observacion,

                            // 🔹 CONTACTO
                            'telefono' => $r->Telefono,
                            'celular' => $r->StrCelular,
                            'email' => $r->Email,
                            'contacto' => $r->Contacto,
                            'direccion' => $r->Direccion,
                        ],

                    ),

                'totales' => $result['totales'],
                'totales_por_asesor' => $result['totales_por_asesor'],
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $result['total'],
                    'last_page' => (int) ceil($result['total'] / $perPage),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /* VISTA EVENTOS / VISITAS */
    public function eventosIndex()
    {
        return view('crm.eventos.index');
    }

    /* DATA EVENTOS / VISITAS */
    public function eventosData(Request $request)
    {
        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);

            // ✅ NORMALIZAR FILTROS (CLAVE)
            $filters = [
                'start' => $request->filled('start') ? $request->get('start') : null,
                'end' => $request->filled('end') ? $request->get('end') : null,
                'asesores' => $request->filled('asesores') ? $request->get('asesores') : null,
                'tipoEvento' => $request->filled('tipoEvento') ? $request->get('tipoEvento') : null,
                'search' => $request->filled('search') ? $request->get('search') : null,
            ];

            $user = Auth::user();

            // 🔐 Seguridad
            if (!in_array($user->perfil_usuario_id, [1, 2, 9])) {
                $filters['asesores'] = [$user->username];
            }

            $result = $this->crmService->listEventos($page, $perPage, $filters);

            return response()->json([
                'success' => true,
                'data' => $result['rows'],
                'totales' => $result['totales'],
                'totales_por_asesor' => $result['totales_por_asesor'],
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $result['total'],
                    'last_page' => (int) ceil($result['total'] / $perPage),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // Exportar Eventos / Visitas CRM
    public function exportEventos(Request $request)
    {
        try {
            // 🔹 Normalizar filtros
            $filters = [
                'start' => $request->input('start'),
                'end' => $request->input('end'),
                'asesores' => $request->input('asesores', []),
                'tipoEvento' => $request->input('tipoEvento'),
                'search' => $request->input('search'),
            ];

            // 🔹 Validación mínima
            if (empty($filters['start']) || empty($filters['end'])) {
                return back()->with('error', 'Debe seleccionar un rango de fechas para exportar.');
            }

            $filename = 'CRM_Eventos_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new EventosExport($filters), $filename);
        } catch (Throwable $e) {
            // 🔥 LOG para TI (no para el usuario)
            logger()->error('Error exportando eventos CRM', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 🧠 Mensaje amable para el usuario
            return back()->with('error', 'No fue posible generar el archivo Excel. Intente nuevamente o contacte a sistemas.');
        }
    }

    /* Listar Asesores con Eventos */
    public function asesoresEventos()
    {
        $asesores = DB::connection('sqlsrv')->select("
            SELECT DISTINCT
                u.StrIdUsuario   AS Usuario,
                u.StrNombre      AS Nombre
            FROM TblEventos tv
            INNER JOIN TblUsuarios u
                ON u.StrIdUsuario = tv.StrRecibio
            WHERE
                u.StrArea = 1
                AND u.IntEstadoUsuario = 1
            ORDER BY u.StrNombre;
        ");

        return response()->json($asesores);
    }

    // Listar Asesores CRM
    public function asesores()
    {
        $asesores = DB::connection('sqlsrv')->select("
            SELECT DISTINCT
                u.StrIdUsuario   AS Usuario,
                u.StrNombre      AS Nombre
            FROM TblOportunidades tv
            INNER JOIN TblUsuarios u
                ON u.StrIdUsuario = tv.Usuario
            WHERE
                u.StrArea = 1
                AND u.IntEstadoUsuario = 1
            ORDER BY u.StrNombre;
        ");

        return response()->json($asesores);
    }

    public function estadoOportunidad(){
        $estados = DB::connection('sqlsrv')->select("
            SELECT
            IntIdEstado AS IdEstado,
            StrDescripcion AS EstadoOportunidad
            FROM TblEstado
            ORDER BY StrDescripcion;
        ");

        return response()->json($estados);
    }


    // Vista detalle Fotos Evento / Visita
    public function fotos(int $evento)
    {
        $fotos = DB::connection('sqlsrv')->select(
            "
            SELECT
                IdSeguridad,
                StrDocumento,
                StrArchivo,
                CONVERT(date, DatFecha) AS Fecha
            FROM TblGestionDocumentos
            WHERE IntEvento = ?
            ORDER BY DatFecha DESC
            ",
            [$evento],
        );

        return response()->json($fotos);
    }

    /// Vista imagen almacenada
    public function verImagen(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            abort(400, 'Id de imagen no enviado');
        }

        $row = DB::connection('sqlsrv')->selectOne(
            "
            SELECT StrArchivo
            FROM TblGestionDocumentos
            WHERE IdSeguridad = ?
        ",
            [$id],
        );

        if (!$row) {
            abort(404, 'Registro no encontrado');
        }

        // 🔥 NORMALIZAR RUTA (ESTE ES EL PASO QUE FALTABA)
        $path = $row->StrArchivo;

        // Si viene con D:\CRM → convertir a UNC
        if (str_starts_with($path, 'D:\\CRM')) {
            $path = str_replace('D:\\CRM', '\\\\192.168.10.244\\CRM', $path);
        }

        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado en red');
        }

        return response()->file($path);
    }



    // Exportar Eventos / Visitas CRM
    public function exportOportunidades(Request $request)
    {
        try {
            // 🔹 Normalizar filtros
            $filters = [
                'start' => $request->input('start'),
                'end' => $request->input('end'),
                'asesores' => $request->input('asesores', []),
                'sector' => $request->input('sector'),
                'search' => $request->input('search'),
            ];

            // 🔹 Validación mínima
            if (empty($filters['start']) || empty($filters['end'])) {
                return back()->with('error', 'Debe seleccionar un rango de fechas para exportar.');
            }

            $filename = 'CRM_Oportunidades_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new OportunidadesExport($filters), $filename);
        } catch (Throwable $e) {
            // 🔥 LOG para TI (no para el usuario)
            logger()->error('Error exportando eventos CRM', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 🧠 Mensaje amable para el usuario
            return back()->with('error', 'No fue posible generar el archivo Excel. Intente nuevamente o contacte a sistemas.');
        }
    }




    /* KPIs */
    public function kpisView()
    {
        return view('crm.kpis');
    }

    /* KPIs */
    public function kpis(Request $request)
    {
        try {
            $filters = [
                'start' => $request->get('start') ?: null,
                'end' => $request->get('end') ?: null,
                'asesor' => $request->get('asesor') ?: null,
            ];

            $user = Auth::user();

            // 🔐 Seguridad
            if (!in_array($user->perfil_usuario_id, [1, 2, 9])) {
                $filters['asesor'] = $user->username;
            }

            $kpis = $this->crmService->kpisSeguimiento($filters);

            return response()->json([
                'success' => true,
                'data' => $kpis,
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
