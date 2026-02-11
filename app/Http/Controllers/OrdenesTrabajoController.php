<?php

namespace App\Http\Controllers;

use App\Events\MaterialSolicitadoEvent;
use App\Models\InstaladorModel;
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

use Carbon\Carbon;

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

    public function consultar(Request $request)
    {
        try {
            $ndoc = $request->input('ndoc');

            $orderDetail = $this->orderWorkService->getOrderDetail($ndoc);

            return response()->json($orderDetail);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json(
                [
                    'error' => true,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $ot = \App\Models\OrderWorkModel::create([
                'n_documento' => $request->n_documento,
                'pedido' => $request->n_documento,
                'tercero' => $request->tercero,
                'vendedor' => $request->vendedor,
                'instalador_id' => $request->instalador_id,
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

        // 🔒 Seguridad: solo instalador
        if ((int) $user->perfil_usuario_id !== 7) {
            abort(403);
        }

        $pedido = $this->orderWorkService->getPedidoHgiPorOT($workOrderId);

        return response()->json($pedido);
    }

    /* función para iniciar una orden de trabajo */
    public function start($id)
    {
        try {
            $workOrder = \App\Models\OrderWorkModel::findOrFail($id);
            $workOrder->status = 'in_progress';
            $workOrder->save();

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
            if ($request->expectsJson()) {
                return response()->json($materials);
            }

            // 👉 CUANDO ES NAVEGACIÓN NORMAL
            $workOrder = OrderWorkModel::with(['instalador', 'pedidosMateriales.instalador', 'pedidosMateriales.items'])->findOrFail($id);

            return view('workorders.asignarherramienta', [
                'materials' => $materials,
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
                'herramienta_id' => 'required|integer',
                'cantidad' => 'required|integer|min:1',
            ]);

            $materialId = $request->herramienta_id;
            $cantidad = $request->cantidad;

            $registro = WorkOrdersMaterialsModel::where('work_order_id', $orderId)->where('material_id', $materialId)->first();

            if ($registro) {
                $registro->cantidad += $cantidad;
                $registro->save();
            } else {
                $registro = WorkOrdersMaterialsModel::create([
                    'work_order_id' => $orderId,
                    'material_id' => $materialId,
                    'cantidad' => $cantidad,
                ]);
            }

            return response()->json([
                'success' => true,
                'item' => [
                    'id_work_order_material' => $registro->id_work_order_material,
                    'id_material' => $materialId,
                    'cantidad' => $registro->cantidad,
                    'nombre' => optional($registro->material)->nombre_material ?? optional($registro->material)->nombre,
                    'codigo' => optional($registro->material)->codigo_material ?? optional($registro->material)->codigo,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error al asignar material',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /*  funcion para buscar materiales */
    public function buscarMaterial(Request $request)
    {
        //
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
            /*$count = WorkOrdersMaterialsModel::where('work_order_id', $id)->count();

            /*if ($count === 0) {
                return redirect()->route('ordenes.trabajo.asignados')->with('error', 'No se puede finalizar la orden de trabajo porque no tiene materiales asignados.');
            }*/

            $ordenTrabajo = OrderWorkModel::findOrFail($id);
            return view('workorders.finalizar', [
                'ordenTrabajo' => $ordenTrabajo,
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
            ->get(['fecha', 'hora_inicio', 'hora_fin', 'horas_trabajadas', 'observaciones']);
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
                    //'installation_notes' => 'required|string|min:10',
                ]);

                // 2️⃣ Finalizar OT (estado general)
                $this->orderWorkService->registrarJornadas($workorder, $request->jornadas, Auth::user()->id);

                return response()->json([
                    'type' => 'success',
                    'message' => 'Jornadas registradas correctamente.'
                ], 200);


        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'type' => 'warning',
                'message' => collect($e->errors())->first()[0]
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'type' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // función para finalizar una orden de trabajo
    public function finalizar(Request $request, int $workorder)
    {
        try {

            $count = OrdenTrabajoModel::where('orden_trabajo_id', $workorder)->count();

            if ($count === 0) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'No se puede finalizar la orden de trabajo porque no tiene jornadas registradas.'
                ], 422);
            }

            $request->validate([
                'installation_notes' => 'required|string|min:10',
            ],
            [
                'installation_notes.required' => 'Las notas de instalación son obligatorias.',
                'installation_notes.string' => 'Las notas de instalación deben ser un texto válido.',
                'installation_notes.min' => 'Las notas de instalación deben tener al menos :min caracteres.',
            ]);

            $this->orderWorkService->finalizarOT(
                $workorder,
                now()->toDateTimeString(),
                $request->installation_notes,
                auth()->id()
            );

            return response()->json([
                'type' => 'success',
                'message' => 'Orden de trabajo finalizada correctamente.'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // función para ver una orden de trabajo finalizada
    public function verOrdenFinalizada($id)
    {
        try {
            $ordenTrabajo = OrderWorkModel::with(['instalador', 'pedidosMateriales.instalador', 'pedidosMateriales.items', 'UsuariosOT'])->findOrFail($id);

            return view('workorders.finalizadashow', [
                'ordenTrabajo' => $ordenTrabajo,
            ]);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }
}
