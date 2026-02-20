<?php

namespace App\Http\Controllers;

use App\Models\DetalleSolicitudMaterialModel;
use App\Models\InstaladorModel;
use App\Models\MaterialModel;
use App\Models\OrderWorkModel;
use App\Models\PedidoMaterialItemModel;
use App\Models\PedidoMaterialModel;
use App\Models\SolicitudMaterialModel;
use App\Services\OrderWorkService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Services\SolicitudService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Str;

use App\Models\ProveedorModel;
use App\Models\WorkOrdersMaterialsModel;

class SolicitudesController
{
    protected $dataSolicitudes;
    protected OrderWorkService $orderWorkService;

    public function __construct(SolicitudService $dataSolicitudes, OrderWorkService $orderWorkService)
    {
        $this->dataSolicitudes = $dataSolicitudes;
        $this->orderWorkService = $orderWorkService;
    }

    // Traer todas las solicitudes
    public function solicitudes()
    {
        $dataSolicitudes = $this->dataSolicitudes->getSolicitudService();

        return view('Solicitudes.index', [
            'dataSolicitudes' => $dataSolicitudes,
        ]);
    }

    //  Ver detalle
    public function show(Request $request, $id)
    {
        $solicitud = $this->dataSolicitudes->getSolicitudIdService($id);

        return view('Solicitudes.ver', [
            'solicitud' => $solicitud,
        ]);
    }
    // ver y crear solicitud
    public function create($id)
    {
        $ordenTrabajo = OrderWorkModel::with('instalador')->findOrFail($id);

        $pedidoMaterial = PedidoMaterialModel::where('orden_trabajo_id', $ordenTrabajo->id_work_order)->first();

        $solicitud = null;

        if ($pedidoMaterial) {
            $solicitud = SolicitudMaterialModel::where('pedido_material_id', $pedidoMaterial->id_pedido_material)->first();
        }

        return view('Solicitudes.crear', compact('ordenTrabajo', 'solicitud'));
    }

    // Buscar
    public function buscarCompra131(Request $request)
    {
        $search = $request->search;

        return DB::connection('sqlsrv')
            ->table('TblDocumentos as t')
            ->join('TblTerceros as tc', 't.StrTercero', '=', 'tc.StrIdTercero')
            ->where('t.IntTransaccion', 131)
            ->where('t.IntEstado', 0)
            ->where('t.IntDocumento', 'like', "%{$search}%")
            ->select('t.IntDocumento', 'tc.StrNombre as proveedor')
            ->orderByDesc('t.IntDocumento')
            ->get();
    }

    // Importar compra desde SQL Server (documento 131)
    public function importarCompra(Request $request)
    {
        DB::beginTransaction();

        try {
            $ordenTrabajo = OrderWorkModel::findOrFail($request->orden_id);

            $pedido = PedidoMaterialModel::where('orden_trabajo_id', $ordenTrabajo->id_work_order)->firstOrFail();

            $solicitud = SolicitudMaterialModel::where('pedido_material_id', $pedido->id_pedido_material)->first();

            if (!$solicitud) {
                $solicitud = SolicitudMaterialModel::create([
                    'pedido_material_id' => $pedido->id_pedido_material,
                    'consecutivo_compra' => $request->documento,
                    'status' => 'in_progress',
                    'ref_id_usuario_registro' => auth()->id(),
                    'fecha_registro' => now(),
                ]);
            } else {
                $solicitud->update([
                    'consecutivo_compra' => $request->documento,
                    'status' => 'in_progress',
                    'ref_id_usuario_modificacion' => auth()->id(),
                    'fecha_modificacion' => now(),
                ]);
            }

            // Eliminar materiales previamente asociados a la orden de trabajo para evitar duplicados
            WorkOrdersMaterialsModel::where(
                'work_order_id',
                $ordenTrabajo->id_work_order
            )->delete();

        
            //  Traer detalles del documento 131
            $items = DB::connection('sqlsrv')
                ->table('TblDetalleDocumentos as d')
                ->join('TblProductos as p', 'p.StrIdProducto', '=', 'd.StrProducto')
                ->where('d.IntDocumento', $request->documento)
                ->where('d.IntTransaccion', 131)
                ->select([
                    'd.StrProducto',
                    'd.IntCantidad',
                    'd.IntValorTotal',
                    'd.IntValorIva',
                    'p.StrDescripcion'
                ])
                ->get();

            foreach ($items as $item) {
                WorkOrdersMaterialsModel::updateOrCreate(
                    [
                        'work_order_id' => $ordenTrabajo->id_work_order,
                        'material_id' => $item->StrProducto,
                    ],
                    [
                        'cantidad' => $item->IntCantidad,
                        'ultimo_costo' => $item->IntValorTotal ?? 0,
                    ],
                );
            }


            DB::commit();

            return response()->json([
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function showSolicitud($pedidoMaterialId)
    {
        $pedido = SolicitudMaterialModel::with(['pedidoMaterial', 'detalles'])->find($pedidoMaterialId);

        if (!$pedido) {
            return redirect()->route('solicitudes.index')->with('error', 'Solicitud no encontrada.');
        }

        return view('Solicitudes.show', compact('pedido'));
    }

    //  Subir archivo Excel
    public function importExcel(Request $request, $id)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xls,xlsx|max:5120',
        ]);

        DB::beginTransaction();

        try {
            //  1. Pedido material (PADRE)
            $pedidoMaterial = PedidoMaterialModel::firstOrCreate(
                ['orden_trabajo_id' => $id],
                [
                    'instalador_id' => $request->instalador_id ?? null,
                    'status' => 'queued',
                    'fecha_solicitud' => now(),
                ],
            );

            if ($pedidoMaterial->status === 'approved') {
                throw new \Exception('La solicitud ya está aprobada y no puede modificarse.');
            }

            $pedidoMaterialId = $pedidoMaterial->id_pedido_material;

            //  GUARDAR ARCHIVO EXCEL COMO ADJUNTO
            $archivo = $request->file('archivo_excel');

            // Generar nombre único para evitar colisiones
            $nombreArchivo = 'solicitud_material_' . now()->format('Ymd_His') . '.' . $archivo->getClientOriginalExtension();

            $ruta = $archivo->storeAs('solicitudes_material/' . $pedidoMaterialId, $nombreArchivo, 'public');

            //  REGISTRAR ADJUNTO EN BD
            DB::table('solicitud_material_adjuntos')->insert([
                'solicitud_material_id' => $pedidoMaterialId,
                'archivo' => $ruta,
                'fecha_registro' => now(),
                'user_reg' => Auth::id(),
            ]);

            //  2. Leer Excel
            $spreadsheet = IOFactory::load($request->file('archivo_excel')->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $proveedorNombre = null;
            $ivaGeneral = 0;
            $materiales = [];

            foreach ($rows as $row) {
                $row = array_map(fn($v) => trim((string) $v), $row);

                //  Proveedor + IVA general
                if (strcasecmp($row['A'], 'proveedor') === 0) {
                    $proveedorNombre = $row['B'] ?? null;
                    $ivaGeneral = is_numeric($row['E']) ? (float) $row['E'] : 0;
                    continue;
                }

                //  Ignorar encabezados
                if (empty($row['A']) || in_array(strtolower($row['A']), ['codigo', 'código', 'col a'])) {
                    continue;
                }

                $cantidad = is_numeric($row['C']) ? (float) $row['C'] : 0;
                $precio = is_numeric($row['D']) ? (float) $row['D'] : 0;
                $totalExcel = is_numeric($row['E']) ? (float) $row['E'] : 0;

                $subtotal = $cantidad * $precio;
                $ivaCalculado = $totalExcel - $subtotal;
                $total = $subtotal + $ivaCalculado;

                $materiales[] = [
                    'solicitud_material_id' => $pedidoMaterialId,
                    'codigo_material' => $row['A'],
                    'descripcion_material' => $row['B'] ?? '',
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'iva' => round($ivaCalculado, 2),
                    'iva_porcentaje' => null,
                    'descuento' => 0,
                    'total' => round($totalExcel, 2),
                ];
            }

            //  3. Proveedor (crear si no existe)
            if (!$proveedorNombre) {
                throw new \Exception('El archivo no contiene el proveedor.');
            }

            $proveedor = ProveedorModel::firstOrCreate(
                ['name_supplier' => $proveedorNombre],
                [
                    'code_supplier' => Str::slug($proveedorNombre),
                    'status' => 'active',
                    'fecha_registro' => now(),
                    'user_reg' => Auth::id(),
                ],
            );

            $pedidoMaterial->update([
                'proveedor_id' => $proveedor->id_supplier,
            ]);

            //  4. Insert / Update detalles
            foreach ($materiales as $material) {
                DetalleSolicitudMaterialModel::updateOrCreate(
                    [
                        'solicitud_material_id' => $material['solicitud_material_id'],
                        'codigo_material' => $material['codigo_material'],
                    ],
                    array_merge($material, [
                        'fecha_registro' => now(),
                        'user_reg' => Auth::id(),
                    ]),
                );
            }

            DB::commit();

            return redirect()->route('solicitudes.show', $pedidoMaterialId)->with('success', 'Solicitud de material importada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    //  Aprobar solicitud
    public function approve($solicitudId)
    {
        $solicitud = SolicitudMaterialModel::with('pedidoMaterial')
        ->findOrFail($solicitudId);

        if (!$solicitud->consecutivo_compra) {
            return back()->with('error', 
                'No puede aprobar la solicitud porque no tiene compra importada.'
            );
        }

        if ($solicitud->status === 'approved') {
            return back()->with('warning', 
                'La solicitud ya fue aprobada anteriormente.'
            );
        }

        try {
            DB::beginTransaction();

            $solicitud->update([
                'status' => 'approved',
                'ref_id_usuario_modificacion' => auth()->id(),
                'fecha_modificacion' => now(),
            ]);

            $solicitud->pedidoMaterial->update([
                'status' => 'approved',
                'fecha_aprobacion' => now(),
                'ref_id_usuario_modificacion' => auth()->id(),
            ]);

            DB::commit();

            return back()->with('success', 
                'Solicitud aprobada correctamente.'
            );

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error('Error aprobando solicitud', [
                'solicitud_id' => $solicitudId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 
                'Ocurrió un error al aprobar la solicitud.'
            );
        }
    }

    //  Registrar material en orden de trabajo (desde solicitud aprobada)
    private function registrarMaterialEnOT(int $orderId, int $materialId, int $cantidad): void
    {
        $registro = WorkOrdersMaterialsModel::where('work_order_id', $orderId)->where('material_id', $materialId)->first();

        if ($registro) {
            $registro->cantidad += $cantidad;
            $registro->save();
        } else {
            WorkOrdersMaterialsModel::create([
                'work_order_id' => $orderId,
                'material_id' => $materialId,
                'cantidad' => $cantidad,
            ]);
        }
    }

    //  Resetear solicitud (eliminar detalles y adjuntos, volver a estado inicial)
    public function reset($pedidoMaterialId)
    {
        try {
            DB::beginTransaction();

            $pedido = PedidoMaterialModel::findOrFail($pedidoMaterialId);

            if ($pedido->status !== 'queued') {
                return back()->with('warning', 'No se puede resetear una solicitud aprobada.');
            }

            // 1️⃣ Eliminar detalles
            DetalleSolicitudMaterialModel::where('solicitud_material_id', $pedidoMaterialId)->delete();

            // 2️⃣ Eliminar adjuntos (BD + archivos)
            $adjuntos = DB::table('solicitud_material_adjuntos')->where('solicitud_material_id', $pedidoMaterialId)->get();

            foreach ($adjuntos as $adjunto) {
                if (\Storage::disk('public')->exists($adjunto->archivo)) {
                    \Storage::disk('public')->delete($adjunto->archivo);
                }
            }

            DB::table('solicitud_material_adjuntos')->where('solicitud_material_id', $pedidoMaterialId)->delete();

            // 3️⃣ Limpiar proveedor (opcional, recomendado)
            $pedido->update([
                'proveedor_id' => null,
            ]);

            DB::commit();

            return back()->with('success', 'Solicitud limpiada correctamente. Puede adjuntar un nuevo Excel.');
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Error al resetear solicitud', [
                'pedido_id' => $pedidoMaterialId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al borrar la solicitud. Intente nuevamente.');
        }
    }

    //  Guardar manualmente material desde el formulario
    public function storeMaterial(Request $request, $id)
    {
        $validated = $request->validate([
            'solicitud_material_id' => 'required|integer',
            'codigo_material' => 'required|string|max:100',
            'cantidad' => 'required|numeric|min:1',
            'precio_unitario' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'fecha_registro' => 'required|date',
            'user_reg' => 'required|string|max:255',
        ]);

        $this->dataSolicitudes->storeMaterialService($validated);

        return redirect()->route('solicitudes.show', $id)->with('success', 'Material agregado correctamente.');
    }
}