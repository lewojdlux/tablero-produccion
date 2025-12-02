<?php

namespace App\Http\Controllers;

use App\Models\InstaladorModel;
use App\Models\MaterialModel;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\SolicitudMaterialModel;

use Exception;

// Services
use Illuminate\Support\Facades\Notification;
use App\Services\AsignarMaterialService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Notifications\NewPedidoMaterial;

class AsignarMaterialController
{
    protected $dataMatrial;
    protected $dataAsignarMaterial;
    protected $dataAsignarMaterialHerramienta;
    protected $asignarMaterialService;
    protected int $paginate = 10;

    public function __construct(AsignarMaterialService $asignarMaterialService)
    {
        $this->asignarMaterialService = $asignarMaterialService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->dataMatrial = $this->asignarMaterialService->getAssignedWorkOrders((int) $this->paginate);
        return view('workorders.asignarmateria', [
            'dataMatrial' => $this->dataMatrial,
        ]);
    }

    // Muestra el formulario para asignar herramientas
    public function asignarHerramientas($id)
    {
        try {
            $this->dataAsignarMaterial = $this->asignarMaterialService->getOrdenTrabajoMaterialesId($id);
            $this->dataAsignarMaterialHerramienta = $this->asignarMaterialService->getMaterialesAsignados($id);

            return view('workorders.asignarherramienta', [
                'orderId' => $id,
                'dataAsignarMaterial' => $this->dataAsignarMaterial,
                'dataAsignarMaterialHerramienta' => $this->dataAsignarMaterialHerramienta,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Elimina una herramienta de la orden
    public function removeHerramientaFromOrder($orderId, $materialId)
    {
        try {
            $deleted = DB::table('work_orders_materials')->where('work_order_id', $orderId)->where('material_id', $materialId)->delete();

            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'No se encontró el registro.'], 404);
            }

            return response()->json(['success' => true], 200);
        } catch (\Throwable $e) {
            Log::error('removeHerramientaFromOrder error: ' . $e->getMessage(), ['order' => $orderId, 'material' => $materialId]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar.'], 500);
        }
    }

    // Busqueda de herramientas
    public function searchHerramientas(Request $request)
    {
        try {
            $q = (string) $request->query('q', '');

            if ($q === '') {
                return response()->json([], 200);
            }

            $results = MaterialModel::query()
                ->where('nombre_material', 'like', "%{$q}%")
                ->orWhere('codigo_material', 'like', "%{$q}%")
                ->limit(10)
                ->get(['id_material', 'nombre_material', 'codigo_material']);

            // Garantizamos que devolvemos un array plano
            return response()->json($results->values()->all(), 200);
        } catch (\Throwable $e) {
            \Log::error('HerramientaController@searchHerramientas error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json(
                [
                    'error' => 'Error en el servidor al buscar herramientas',
                ],
                500,
            );
        }
    }

    // Agrega una herramienta a la orden
    public function addHerramientaToOrder(Request $request, $orderId)
    {
        $request->validate([
            'herramienta_id' => 'required|integer|exists:materiales,id_material',
            'cantidad' => 'nullable|integer|min:1',
        ]);

        $herramientaId = $request->input('herramienta_id');
        $cantidad = $request->input('cantidad', 1);

        // Opción A (recomendada si tienes relación many-to-many):
        // $order = OrderWorkModel::findOrFail($orderId);
        // $order->herramientas()->attach($herramientaId, ['cantidad' => $cantidad]);

        // Opción B (insert directo en tabla pivot 'order_work_herramienta'):
        DB::table('work_orders_materials')->insert([
            'work_order_id' => $orderId, // ajusta a tu columna
            'material_id' => $herramientaId,
            'cantidad' => $cantidad,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Obtener la herramienta agregada para devolver al frontend
        $herramienta = MaterialModel::find($herramientaId);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $herramienta->id_material,
                'codigo' => $herramienta->codigo_material ?? null,
                'nombre' => $herramienta->nombre_material,
                'cantidad' => $cantidad,
            ],
        ]);
    }

    // Asignar Materiales
    public function storeSolicitudMaterial(Request $request, $orderId)
    {
        // validación
        $data = $request->validate([
            'orden_trabajo_id' => 'required|integer', // lo verificamos con $orderId
            'codigo' => 'nullable|string|max:100',
            'nombre' => 'required|string|max:1000',
            'cantidad' => 'required|integer|min:1',
            'observacion' => 'nullable|string|max:2000',
        ]);

        // Obtener el ID del usuario autenticado
        $userId = Auth::user()->id;
        if (!$userId) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        // Buscar el instalador asociado al usuario
        $instalador = InstaladorModel::where('identificador_usuario', $userId)->first();
        if (!$instalador) {
            return response()->json(['message' => 'Instalador no encontrado.'], 404);
        }

        $ordenTrabajoId = $orderId;

        DB::beginTransaction();
        try {
            // Consultar si el pedido ya existe
            $pedido = DB::table('pedidos_materiales')->where('orden_trabajo_id', $data['orden_trabajo_id'])->first();

            if ($pedido) {
                $pedidoId = $pedido->id_pedido_material;
            } else {
                // === Crear nuevo pedido ===
                $pedidoId = DB::table('pedidos_materiales')->insertGetId(
                    [
                        'orden_trabajo_id' => $data['orden_trabajo_id'],
                        'instalador_id' => $instalador->id_instalador,
                        'fecha_solicitud' => now(), // ✅ formato correcto
                        'status' => 'pending',
                        'fecha_aprobacion' => null,
                        'observaciones' => $data['observacion'] ?? null,
                        'ref_id_usuario_registro' => $userId,
                        'fecha_modificacion' => null,
                        'ref_id_usuario_modificacion' => null,
                    ],
                    'id_pedido_material',
                );
            }

            // === 5️⃣ Insertar ítem del pedido ===
            DB::table('pedidos_materiales_item')->insert([
                'pedido_material_id' => $pedidoId,
                'codigo_material' => $data['codigo'] ?? null,
                'descripcion_material' => $data['observacion'] ?? null,
                'cantidad' => (int) $data['cantidad'],
            ]);

            // === 6️⃣ Insertar o actualizar solicitud_material ===
            $solicitudExistente = DB::table('solicitud_material')->where('pedido_material_id', $pedidoId)->first();

            $dataSolicitud = [
                'pedido_material_id' => $pedidoId,
                'instalador_id' => $instalador->id_instalador,
                'ref_id_usuario_registro' => $userId,
                'fecha_registro' => now(),
            ];

            if ($solicitudExistente) {
                DB::table('solicitud_material')->where('id_solicitud_material', $solicitudExistente->id_solicitud_material)->update($dataSolicitud);
                $solicitudId = $solicitudExistente->id_solicitud_material;
            } else {
                $solicitudId = DB::table('solicitud_material')->insertGetId($dataSolicitud, 'id_solicitud_material');
            }

            // === 7️⃣ Insertar detalle de solicitud ===
            DB::table('detalle_solicitud_material')->insert([
                'solicitud_material_id' => $solicitudId,
                'codigo_material' => $data['codigo'] ?? null,
                'cantidad' => (int) $data['cantidad'],
                'precio_unitario' => null,
                'user_reg' => $userId,
            ]);

            DB::commit();

            // === 8️⃣ Notificar administradores ===
            try {
                $payload = [
                    'pedido_material_id' => $pedidoId,
                    'orden_trabajo_id' => $orderId,
                    'instalador_id' => $instalador->id_instalador,
                    'descripcion' => $data['observacion'] ?? '',
                    'url' => route('asignar.material.index') . "?order={$ordenTrabajoId}&pedido={$pedidoId}",
                ];

                $admins = User::whereIn('perfil_usuario_id', [1, 2])->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new NewPedidoMaterial($payload));
                }
            } catch (Exception $notifyEx) {
                Log::error('Error enviando notificación: ' . $notifyEx->getMessage(), [
                    'pedido_material_id' => $pedidoId,
                ]);
            }

            // === 9️⃣ Respuesta final ===
            return response()->json([
                'success'            => true,
                'message'            => 'Solicitud registrada correctamente.',
                'pedido_material_id' => $pedidoId,
            ], 201);


        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('storeSolicitudMaterial error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'Error interno al registrar la solicitud.'], 500);
        }
    }
}