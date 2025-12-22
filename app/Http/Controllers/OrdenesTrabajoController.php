<?php

namespace App\Http\Controllers;

use App\Events\MaterialSolicitadoEvent;
use App\Models\InstaladorModel;
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
    public function index()
    {
        //

        try {
            $ordenesTrabajo = $this->orderWorkService->getOrdenesTrabajo();
            $instaladores = InstaladorModel::all();

            return view('workorders.index', [
                'dataMatrial' => $ordenesTrabajo,
                'instaladores' => $instaladores,
            ]);
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
            $ordenesTrabajo = $this->orderWorkService->getOrderAsignados();

            // Si el usuario no es admin NO tiene sentido mostrar notificaciones
            $usuario = auth()->user();

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
                Notification::send($user, new NewPedidoMaterial([
                    'pedido_id' => $pedido->id_pedido_material,
                    'material' => [
                        'codigo' => $item->codigo_material,
                        'descripcion' => $item->descripcion_material,
                        'cantidad' => $item->cantidad,
                    ],
                ]));
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


    public function show($pedidoId)
    {




            $pedido = PedidoMaterialModel::with([
                'ordenTrabajo.instalador',
                'items',
                'instalador'
            ])->findOrFail($pedidoId);

            return view('workorders.pedidosmateriales', [
                'pedido' => $pedido,
                'ordenTrabajo' => $pedido->ordenTrabajo,
                'items' => $pedido->items,
            ]);


    }
}