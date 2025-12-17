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
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
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

            $notificaciones = in_array($usuario->perfil_usuario_id, [1,2])
                ? $usuario->unreadNotifications()->orderBy('created_at', 'desc')->get()
                : collect(); // vacío para instaladores

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
    public function indexMaterialesOrdenes($id)
    {
        try {
            $orderId = $id;
            $materials = $this->orderWorkService->getMaterialsByOrderId($orderId);

            return view('workorders.asignarmateria', [
                'materials' => $materials,
                'orderId' => $orderId,
            ]);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->view('errors.500', ['message' => $e->getMessage()], 500);
        }
    }

    /*  funcion para asignar material a una orden de trabajo */
    public function asignarMaterial($orderId, $materialId, Request $request)
    {
        try {
            $cantidad = $request->input('cantidad');

            if ($cantidad < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cantidad debe ser mayor a 0.',
                ]);
            }

            // Aquí guardas el registro en tu tabla pivot o la tabla que manejes
            WorkOrdersMaterialsModel::create([
                'work_order_id' => $orderId,
                'material_id' => $materialId,
                'cantidad' => $cantidad,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Material asignado correctamente.',
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
            $deleted = WorkOrdersMaterialsModel::where('id_work_order_material', $womId)
                ->where('work_order_id', $orderId)
                ->where('material_id', $materialId)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el material para eliminar.'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Material eliminado correctamente.'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function solicitarMaterial(Request $request)
    {
        try {


            $request->validate([
                'orden_trabajo_id' => 'required|integer',
                'codigo_material' => 'required|string',
                'descripcion_material' => 'required|string',
                'cantidad' => 'required|integer|min:1',
            ]);

            // IDENTIFICADOR DEL INSTALADOR DESDE USERS
            $identificador = Auth::user()->identificador_instalador;

            // BUSCAR EL INSTALADOR REAL
            $instalador = InstaladorModel::where('identificador_usuario', $identificador)->first();


            $pedido = PedidoMaterialModel::create([
                'orden_trabajo_id' => $request->orden_trabajo_id,
                'material_id' => null,
                'instalador_id' => $instalador->id_instalador,
                'status' => 'queued',
                'fecha_solicitud' => now(),
                'observaciones' => $request->observaciones,
                'fecha_registro' => now(),
            ]);

            $item = PedidoMaterialItemModel::create([
                'pedido_material_id' => $pedido->id_pedido_material,
                'codigo_material' => $request->codigo_material,
                'descripcion_material' => $request->descripcion_material,
                'cantidad' => $request->cantidad
            ]);


            // -----------------------------------------
            // ENVIAR NOTIFICACIÓN A ADMIN Y COORDINADOR
            // -----------------------------------------

            $usuariosDestino = User::whereIn('perfil_usuario_id', [1, 2]) // ejemplo: admin = 1, coordinador = 2
                                ->get();

            Notification::send($usuariosDestino, new MaterialSolicitadoNotification($pedido, $item));


            // DATOS QUE VIAJARÁN EN EL EVENTO
            $payload = [
                'title' => 'Nueva solicitud de material',
                'message' => "Material: {$item->descripcion_material} (Cant: {$item->cantidad})",
                'pedido_id' => $pedido->id_pedido_material,
                'created_at' => now()->toDateTimeString(),
            ];


            // ENVIAR EVENTO WEBSOCKET
            //broadcast(new MaterialSolicitadoEvent($payload))->toOthers();
            broadcast(new MaterialSolicitadoEvent($payload));





            return response()->json([
                'success' => true,
                'message' => 'Solicitud registrada correctamente'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
