<?php

namespace App\Http\Controllers;

use App\Events\MaterialSolicitadoEvent;
use App\Exports\OrdenTrabajoFinancieroExport;
use App\Models\InstaladorModel;
use App\Models\OrdenTrabajoJornadaModel;
use App\Models\OrdenTrabajoModel;
use App\Models\OrderWorkModel;
use App\Models\PedidoMaterialItemModel;
use App\Models\PedidoMaterialModel;
use App\Models\User;
use App\Models\WorkOrdersMaterialsModel;
use Illuminate\Http\Request;

use App\Services\OrderWorkService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Notifications\MaterialSolicitadoNotification;
use App\Notifications\NewPedidoMaterial;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class OrdenesTrabajoController
{
    protected OrderWorkService $orderWorkService;

    public function __construct(OrderWorkService $orderWorkService)
    {
        $this->orderWorkService = $orderWorkService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //

        $user = Auth::user();

        $vendedor = null;
        if ((int) $user->perfil_usuario_id === 5) {
            $vendedor = $user->identificador_asesor;
        }

        $ordenesTrabajo = $this->orderWorkService->getOrdenesTrabajo($request->search, $vendedor);
        $instaladores = InstaladorModel::all();

        return view('workorders.index', [
            'dataMatrial' => $ordenesTrabajo,
            'instaladores' => $instaladores,
        ]);
        try {
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        try {
            $instaladores = InstaladorModel::all();

            return view('workorders.registrordenes', [
                'instaladores' => $instaladores,
            ]);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }

 

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $pdValido = \DB::connection('sqlsrv')
                ->table('TblDocumentos as t')
                ->join('TblDetalleDocumentos as dd', 'dd.IntDocumento', '=', 't.IntDocumento')
                ->join('TblProductos as p', 'p.StrIdProducto', '=', 'dd.StrProducto')
                ->where('t.IntDocumento', $request->pd_servicio)
                ->where('t.StrDVendedor', $request->vendedor_username)
                ->where('t.StrTercero', $request->tercero_id)
                ->where('p.StrLinea', '40')
                ->exists();

            if (!$pdValido) {
                return response()->json([
                    'success' => false,
                    'message' => 'El PD de servicio no corresponde al cliente o asesor.'
                ]);
            }



            $ot = \App\Models\OrderWorkModel::create([
                'n_documento' => $request->n_documento,
                'pedido' => $request->n_documento,
                'tercero' => $request->tercero,
                'vendedor' => $request->vendedor,
                'instalador_id' => null,
                'pd_servicio'   => $request->pd_servicio,
                'periodo' => $request->periodo,
                'ano' => $request->ano,
                'estado_factura' => $request->status,
                'n_factura' => $request->n_factura,
                'status' => $request->status,
                'description' => $request->obsv_pedido,
                'usereg_ot' => Auth::user()->id,
            ]);

            
            return response()->json([
                'success' => true,
                'data' => $ot,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /* funcion para obtener las órdenes de trabajo asignadas */
    public function indexAsignados()
    {
        //
        try {
            $usuario = Auth::user();

            $vendedor = null;
            if ((int) $usuario->perfil_usuario_id === 5) {
                $vendedor = $usuario->id;
            }

            $ordenesTrabajo = $this->orderWorkService->getOrderAsignados($vendedor);

            $notificaciones = in_array($usuario->perfil_usuario_id, [1, 2]) ? $usuario->unreadNotifications()->orderBy('created_at', 'desc')->get() : collect(); // vacío para instaladores

            return view('workorders.asignados', [
                'dataMatrial' => $ordenesTrabajo,
                'notificaciones' => $notificaciones,
            ]);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }

    /* función para obtener el material de una orden de trabajo HGI */
    public function verPedidoMaterialHgi($workOrderId)
    {
     
        $user = Auth::user();
        $rolesPermitidos = [1, 2, 6, 7];

        if (!in_array((int) $user->perfil_usuario_id, $rolesPermitidos, true)) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $pedido = $this->orderWorkService->getPedidoHgiPorOT($workOrderId);

        return response()->json($pedido);
    }

    /* función para iniciar una orden de trabajo */
    public function start($id)
    {
        try {

        
            $this->orderWorkService->iniciarOrdenTrabajo((int) $id);

            return response()->json([
                'success' => true,
                'message' => 'Orden de trabajo iniciada correctamente.',
            ]);

            
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /* funcion para obtener los materiales de una orden de trabajo */
    public function indexMaterialesOrdenes(Request $request, $id)
    {

        try {
            $materials = $this->orderWorkService->getMaterialsByOrderId($id);

            // 👉 CUANDO VIENE DE VUE (fetch)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($materials);
            }

            // 👉 CUANDO ES NAVEGACIÓN NORMAL
            $workOrder = OrderWorkModel::with(['instalador'])->findOrFail($id);

            return view('workorders.asignarherramienta', [
                'dataAsignarMaterialHerramienta' => $materials, // 👈 ESTE ES EL NOMBRE QUE USA VUE
                'orderId' => $id,
                'dataAsignarMaterial' => $workOrder,
            ]);


        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }

    /*  funcion para asignar material a una orden de trabajo */
    public function asignarMaterial(Request $request, $orderId)
    {
        try {

            $request->validate([
                'herramienta_id' => 'required|string',
                'cantidad' => 'required|integer|min:1',
            ]);

            $codigoProducto = $request->herramienta_id;
            $cantidad = $request->cantidad;

            // 🔥 Obtener costo automáticamente desde SQL Server
            $costoUnitario = $this->orderWorkService
                ->getCostoActualProducto($codigoProducto);

            // 🔎 Buscar si ya existe
            $registro = WorkOrdersMaterialsModel::where('work_order_id', $orderId)
                ->where('material_id', $codigoProducto)
                ->first();

            if ($registro) {

                $registro->cantidad += $cantidad;
                $registro->ultimo_costo = $costoUnitario; // actualizar costo
                $registro->save();

            } else {

                $registro = WorkOrdersMaterialsModel::create([
                    'work_order_id' => $orderId,
                    'material_id' => $codigoProducto,
                    'cantidad' => $cantidad,
                    'ultimo_costo' => $costoUnitario,
                ]);
            }

            return response()->json([
                'success' => true,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /*  funcion para buscar materiales */
    public function buscarMaterial(Request $request)
    {
        //

         $materialName = $request->input('q');
            $materials = $this->orderWorkService->getMaterialsByMaterialName($materialName);

            return response()->json($materials);
        try {
            $materialName = $request->input('q');
            $materials = $this->orderWorkService->getMaterialsByMaterialName($materialName);

            return response()->json($materials);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }

    /* funcion para eliminar material asignado de una orden de trabajo */
    public function eliminarMaterial($orderId, $materialId, $womId)
    {
        try {
            $deleted = WorkOrdersMaterialsModel::where('id_work_order_material', $womId)->where('work_order_id', $orderId)->where('material_id', $materialId)->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el material para eliminar.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Material eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // función para solicitar material (crear pedido de material)
    public function solicitarMaterial(Request $request)
    {
        try {
            $request->validate([
                'orden_trabajo_id' => 'required|integer',
                'codigo_material' => 'required|string',
                'observacion' => 'required|string',
                'cantidad' => 'required|integer|min:1',
            ]);

            $usuario = Auth::user();

            $instalador = InstaladorModel::where('identificador_usuario', $usuario->identificador_instalador)->firstOrFail();

            // ✅ 1 SOLO PEDIDO POR OT + INSTALADOR
            $pedido = PedidoMaterialModel::where('orden_trabajo_id', $request->orden_trabajo_id)->where('instalador_id', $instalador->id_instalador)->where('status', 'queued')->first();

            if (!$pedido) {
                $pedido = PedidoMaterialModel::create([
                    'orden_trabajo_id' => $request->orden_trabajo_id,
                    'instalador_id' => $instalador->id_instalador,
                    'status' => 'queued',
                    'fecha_solicitud' => now(),
                    'fecha_registro' => now(),
                ]);
            }

            // ✅ ITEMS: MISMO CÓDIGO → SUMA
            $item = PedidoMaterialItemModel::where('pedido_material_id', $pedido->id_pedido_material)->where('codigo_material', $request->codigo_material)->first();

            if ($item) {
                $item->cantidad += $request->cantidad;
                $item->descripcion_material = $request->observacion;
                $item->save();
            } else {
                $item = PedidoMaterialItemModel::create([
                    'pedido_material_id' => $pedido->id_pedido_material,
                    'codigo_material' => $request->codigo_material,
                    'descripcion_material' => $request->observacion,
                    'cantidad' => $request->cantidad,
                ]);
            }

            // 🔔 NOTIFICACIÓN (DB)
            $payload = [
                'pedido_id' => $pedido->id_pedido_material,
                'orden_trabajo_id' => $pedido->orden_trabajo_id,
                'instalador_id' => $pedido->instalador_id,
                'material' => [
                    'codigo' => $item->codigo_material,
                    'descripcion' => $item->descripcion_material,
                    'cantidad' => $item->cantidad,
                ],
                'created_at' => now()->toDateTimeString(),
            ];

            $usuariosDestino = User::whereIn('perfil_usuario_id', [1, 2])->get();
            foreach ($usuariosDestino as $user) {
                Notification::send(
                    $user,
                    new NewPedidoMaterial([
                        'pedido_id' => $pedido->id_pedido_material,
                        'material' => [
                            'codigo' => $item->codigo_material,
                            'descripcion' => $item->descripcion_material,
                            'cantidad' => $item->cantidad,
                        ],
                    ]),
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Solicitud registrada correctamente',
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

    // función para mostrar el detalle de un pedido de materiales por orden de trabajo
    public function verPedidoMaterial($orderId)
    {
        try {
            $ordenTrabajo = OrderWorkModel::with(['instalador', 'pedidosMateriales.instalador', 'pedidosMateriales.items'])->findOrFail($orderId);

            return view('workorders.pedidosmateriales', [
                'ordenTrabajo' => $ordenTrabajo,
            ]);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }

    // función para mostrar el detalle de una orden de trabajo
    public function verOrden($orderId)
    {
        try {
            $ordenTrabajo = OrderWorkModel::with(['instalador', 'pedidosMateriales.instalador', 'pedidosMateriales.items'])->findOrFail($orderId);

            return view('workorders.pedidosmateriales', [
                'ordenTrabajo' => $ordenTrabajo,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // función para obtener los materiales de una orden de trabajo en formato JSON (para Vue)
    public function getMaterialesJson($id)
    {
        try {
            $materials = $this->orderWorkService->getMaterialsByOrderId($id);
            return response()->json($materials);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // función para mostrar el detalle de un pedido de materiales
    public function show($pedidoId)
    {
        $pedido = PedidoMaterialModel::with(['ordenTrabajo.instalador', 'items', 'instalador'])->findOrFail($pedidoId);

        return view('workorders.pedidosmateriales', [
            'pedido' => $pedido,
            'ordenTrabajo' => $pedido->ordenTrabajo,
            'items' => $pedido->items,
        ]);
    }

    // función para mostrar el formulario de finalización de orden de trabajo
    public function finalizarForm($id)
    {
        try {
      
            $ordenTrabajo = OrderWorkModel::with('acompanantes')
            ->findOrFail($id);

            $instaladores = \DB::table('instalador')
            ->where('status', 'active')
            ->select('id_instalador', 'nombre_instalador')
            ->orderBy('nombre_instalador')
            ->get();

            // 🔹 Principal
            $principal = $ordenTrabajo->instalador_id;

            // 🔹 Acompañantes
            $acompanantes = $ordenTrabajo->acompanantes
            ->pluck('id_instalador')
            ->map(fn($id) => (int) $id)
            ->toArray();

            return view('workorders.finalizar', [
                'ordenTrabajo' => $ordenTrabajo,
                'instaladores' => $instaladores,
                'principal' => $principal,
                'acompanantes' => $acompanantes,
            ]);

        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }

    // función para obtener las jornadas de una orden de trabajo
    public function jornadas($id)
    {
        return DB::table('orden_trabajo_jornadas')
            ->where('orden_trabajo_id', $id)
            ->orderBy('fecha')
            ->get(['id',  'numero_jornada', 'fecha', 'hora_inicio', 'hora_fin', 'horas_trabajadas', 'observaciones']);
    }

    // función para registrar jornadas de una orden de trabajo
    public function OTJornada(Request $request, int $workorder)
    {
        try {
            // 1️⃣ Validación
            $request->validate([
                'jornadas' => 'required|array|min:1',
                'jornadas.*.fecha' => 'required|date',
                'jornadas.*.hora_inicio' => 'required',
                'jornadas.*.hora_fin' => 'required',
                'jornadas.*.observaciones' => 'nullable|string',
                'jornadas.*.instaladores' => 'nullable|array',
                //'installation_notes' => 'required|string|min:10',
            ]);

            // 2️⃣ Finalizar OT (estado general)
            $this->orderWorkService->registrarJornadas($workorder, $request->jornadas, Auth::user()->id);

            return response()->json(
                [
                    'type' => 'success',
                    'message' => 'Jornadas registradas correctamente.',
                ],
                200,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'type' => 'warning',
                    'message' => collect($e->errors())->first()[0],
                ],
                422,
            );
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // Función para actualizar una jornada
    public function actualizarJornada(Request $request,  $id)
    {
        $request->validate([
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
        ]);

        $jornada = OrdenTrabajoJornadaModel::findOrFail($id);

        $inicio = \Carbon\Carbon::parse($jornada->fecha . ' ' . $request->hora_inicio);
        $fin    = \Carbon\Carbon::parse($jornada->fecha . ' ' . $request->hora_fin);

        if ($fin->lte($inicio)) {
            return response()->json([
                'type' => 'warning',
                'message' => 'La hora final debe ser mayor a la hora inicial.'
            ], 422);
        }

        $jornada->update([
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'horas_trabajadas' => round($inicio->diffInMinutes($fin) / 60, 2),
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Jornada actualizada.'
        ]);
    }

    // función para finalizar una orden de trabajo
    public function finalizar(Request $request, int $workorder)
    {
        try {
            $count = OrdenTrabajoModel::where('orden_trabajo_id', $workorder)->count();

            if ($count === 0) {
                return response()->json(
                    [
                        'type' => 'error',
                        'message' => 'No se puede finalizar la orden de trabajo porque no tiene jornadas registradas.',
                    ],
                    422,
                );
            }

            $request->validate(
                [
                    'installation_notes' => 'nullable|string',
                ],
                [
                    //'installation_notes.required' => 'Las notas de instalación son obligatorias.',
                    'installation_notes.string' => 'Las notas de instalación deben ser un texto válido.',
                   //'installation_notes.min' => 'Las notas de instalación deben tener al menos :min caracteres.',
                ],
            );

            $this->orderWorkService->finalizarOT($workorder, now()->toDateTimeString(), $request->installation_notes, auth()->id());

            return response()->json([
                'type' => 'success',
                'message' => 'Orden de trabajo finalizada correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // función para ver una orden de trabajo finalizada
    public function verOrdenFinalizada($id)
    {
        try {

            $data = $this->orderWorkService->obtenerResumenFinal((int) $id);

            return view('workorders.finalizadashow', $data);

        } catch (\Throwable $e) {

            \Log::error('Error al abrir OT finalizada', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('workorders.index')
                ->with('error', 'Ocurrió un error cargando la orden.');
        }
    }

    // función para obtener el cálculo de mano de obra de una orden de trabajo
    public function manoDeObra(Request $request, $id)
    {
        $user = Auth::user();
        $perfilAutorizado = [1, 2, 6]; // ADMIN, SUPER y ASESOR

        // Seguridad extra por backend
        if (!in_array($user->perfil_usuario_id, $perfilAutorizado)) {
            abort(403);
        }

        $ordenTrabajo = OrderWorkModel::findOrFail($id);
        $pedido = $ordenTrabajo->pd_servicio;
        //$pedido = $request->pedido;

        // 🔹 Mano de obra
        $manoObra = DB::table('vw_calculo_mano_obra_ot')->where('id_work_order', $id)->get();

        $manoObraTotal = $manoObra->sum('total');

        /// cálcular si tiene servicios adicionales a la OT
        $solicitudTotal =
            DB::table('work_orders_materials')
        ->where('work_order_id', $id)
        ->selectRaw('
            CAST(
                SUM(IFNULL(ultimo_costo,0))
            AS DECIMAL(18,2)
            ) as total_material
        ')
        ->value('total_material') ?? 0;

        // 🔹 Pedido HGI (línea 40)
        $pedidoTotal =
            DB::connection('sqlsrv')
                ->table('TblDetalleDocumentos as d')
                ->join('TblProductos as p', 'p.StrIdProducto', '=', 'd.StrProducto')
                ->where('d.IntDocumento', $pedido)
                ->where('d.IntTransaccion', 109)
                ->where('p.StrLinea', 40)
                ->selectRaw(
                    '
                        CAST(
                            SUM(
                                (d.IntCantidad * d.IntValorUnitario)
                                - ISNULL(d.IntValorDescuento,0)
                            )
                        AS DECIMAL(18,2)) as total_pedido
                    ',
                )
                ->value('total_pedido') ?? 0;

        $utilidad = $pedidoTotal - $manoObraTotal - $solicitudTotal;

        $porcentajeUtilidad = 0;

        if ($pedidoTotal > 0) {
            $porcentajeUtilidad = round(($utilidad / $pedidoTotal) * 100, 2);
        }

        return response()->json([
            'mano_obra' => $manoObra,
            'mano_obra_total' => $manoObraTotal,
            'solicitud_total' => $solicitudTotal,
            'pedido_total' => $pedidoTotal,
            'utilidad' => $pedidoTotal - $manoObraTotal - $solicitudTotal,
            'porcentaje_utilidad' => $porcentajeUtilidad,
        ]);
    }

    // función para exportar el resumen financiero de una orden de trabajo a Excel
    public function exportarFinancieroExcel($id)
    {

   
        try {
            // Validar que la OT exista
            $orden = \DB::table('work_orders')->where('id_work_order', $id)->first();


            if (!$orden) {
                return redirect()->back()->with('error', 'La orden de trabajo no existe.');
            }

            // Validar que tenga pedido
            if (empty($orden->pd_servicio)) {
                return redirect()->back()->with('error', 'La OT no tiene pedido asociado.');
            }

            return Excel::download(new OrdenTrabajoFinancieroExport($id), "OT_{$id}_Resumen_Financiero.xlsx");
        } catch (\Throwable $e) {
            // Log interno
            Log::error('Error exportando OT financiero', [
                'id_ot' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al generar el Excel.');
        }
    }

    // Función para buscar PDServicio
    public function buscarPDServicio(Request $request)
    {
        $search    = $request->search;
        $vendedor  = $request->vendedor;
        $tercero   = $request->tercero;

        $result = \DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblDetalleDocumentos as dd', 'dd.IntDocumento', '=', 't.IntDocumento')
            ->join('TblProductos as p', 'p.StrIdProducto', '=', 'dd.StrProducto')
            ->where('t.IntTransaccion', 109)
            ->where('t.StrDVendedor', $vendedor)  
            ->where('t.StrTercero', $tercero)      
            ->where('p.StrLinea', '40')      
            ->where('t.IntDocumento', 'like', "%{$search}%")
            ->select([
                't.IntDocumento',
                't.StrTercero',
                't.StrDVendedor',
                'p.StrDescripcion as Descripcion',
            ])
            ->distinct()
            ->take(15)
            ->get();

        return response()->json($result);
    }

    // función para obtener los instaladores asignados a una orden de trabajo
    public function instaladoresActuales($id)
    {
        $ot = OrderWorkModel::with('acompanantes')
            ->findOrFail($id);

        return response()->json([
            'principal' => $ot->instalador_id,
            'acompanantes' => $ot->acompanantes->pluck('id_instalador')
        ]);
    }


    /* función para asignar instaladores */
    public function asignarInstaladores(Request $request)
    {
        $request->validate([
            'work_order_id'        => 'required|exists:work_orders,id_work_order',
            'instalador_principal' => 'required|exists:instalador,id_instalador',
            'acompanantes'         => 'nullable|array',
            'acompanantes.*'       => 'exists:instalador,id_instalador',
        ]);

        try {
                // Asignar instaladores
                $this->orderWorkService->asignarInstaladores(
                    $request->work_order_id,
                    $request->instalador_principal,
                    $request->acompanantes ?? []
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Asignación guardada correctamente.'
                ]);

        }catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un problema al guardar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}