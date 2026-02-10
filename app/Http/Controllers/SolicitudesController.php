<?php

namespace App\Http\Controllers;

use App\Models\DetalleSolicitudMaterialModel;
use App\Models\InstaladorModel;
use App\Models\MaterialModel;
use App\Models\OrderWorkModel;
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

    // 🔹 Ver detalle
    public function show(Request $request, $id)
    {
        $solicitud = $this->dataSolicitudes->getSolicitudIdService($id);

        return view('Solicitudes.ver', [
            'solicitud' => $solicitud,
        ]);
    }

    // 🔹 Formulario para cargar solicitud (Excel)
    public function create($id)
    {
        $ordenTrabajo = OrderWorkModel::with([
            'instalador',
            'pedidosMateriales'
        ])->find($id);

        if (!$ordenTrabajo) {
            return redirect()
                ->route('solicitudes.index')
                ->with('error', 'Orden de trabajo no encontrada');
        }

        // 🔹 VALIDAR SI YA EXISTE SOLICITUD DE MATERIAL
        $pedidoMaterial = $ordenTrabajo->pedidosMateriales->first();

        if ($pedidoMaterial) {
            // 👉 Ya existe → ir al SHOW para agregar / reimportar
            return redirect()
                ->route('solicitudes.show', $pedidoMaterial->id_pedido_material)
                ->with('warning', 'Esta orden ya tiene una solicitud de material. Puede actualizarla adjuntando otro Excel.');
        }

        // 👉 No existe → continuar al formulario normal
        return view('Solicitudes.crear', [
            'ordenTrabajo' => $ordenTrabajo,
        ]);
    }

    // 🔹 Subir archivo Excel
    public function importExcel(Request $request, $id)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xls,xlsx|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // 🔹 1. Pedido material (PADRE)
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


            // 🔹 GUARDAR ARCHIVO EXCEL COMO ADJUNTO
            $archivo = $request->file('archivo_excel');

            // Generar nombre único para evitar colisiones
            $nombreArchivo = 'solicitud_material_' . now()->format('Ymd_His') . '.' . $archivo->getClientOriginalExtension();

            $ruta = $archivo->storeAs(
                'solicitudes_material/' . $pedidoMaterialId,
                $nombreArchivo,
                 'public'
            );

            // 🔹 REGISTRAR ADJUNTO EN BD
            DB::table('solicitud_material_adjuntos')->insert([
                'solicitud_material_id' => $pedidoMaterialId,
                'archivo' => $ruta,
                'fecha_registro' => now(),
                'user_reg' => Auth::id(),
            ]);

            // 🔹 2. Leer Excel
            $spreadsheet = IOFactory::load($request->file('archivo_excel')->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $proveedorNombre = null;
            $ivaGeneral = 0;
            $materiales = [];

            foreach ($rows as $row) {
                $row = array_map(fn($v) => trim((string) $v), $row);

                // 🔹 Proveedor + IVA general
                if (strcasecmp($row['A'], 'proveedor') === 0) {
                    $proveedorNombre = $row['B'] ?? null;
                    $ivaGeneral = is_numeric($row['E']) ? (float) $row['E'] : 0;
                    continue;
                }

                // 🔹 Ignorar encabezados
                if (empty($row['A']) || in_array(strtolower($row['A']), ['codigo', 'código', 'col a'])) {
                    continue;
                }

                $cantidad = is_numeric($row['C']) ? (float) $row['C'] : 0;
                $precio = is_numeric($row['D']) ? (float) $row['D'] : 0;

                $subtotal = $cantidad * $precio;
                $ivaCalculado = $subtotal * ($ivaGeneral / 100);
                $total = $subtotal + $ivaCalculado;

                $materiales[] = [
                    'solicitud_material_id' => $pedidoMaterialId,
                    'codigo_material' => $row['A'],
                    'descripcion_material' => $row['B'] ?? '',
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'iva' => $ivaCalculado,
                    'iva_porcentaje' => $ivaGeneral,
                    'descuento' => 0,
                    'total' => $total,
                ];
            }

            // 🔹 3. Proveedor (crear si no existe)
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

            // 🔹 4. Insert / Update detalles
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


    // 🔹 Aprobar solicitud
    public function approve($pedidoMaterialId)
    {
        try {
            DB::beginTransaction();

            $pedido = PedidoMaterialModel::where('id_pedido_material', $pedidoMaterialId)->firstOrFail();

            // Validación defensiva
            if ($pedido->status !== 'queued') {
                return back()->with('warning', 'La solicitud ya fue procesada anteriormente.');
            }

            // 1️⃣ Cambiar estado del pedido
            $pedido->update([
                'status' => 'approved',
                'fecha_aprobacion' => now(),
                'ref_id_usuario_modificacion' => auth()->id(),
            ]);

            // 2️⃣ Registrar materiales en catálogo
            foreach ($pedido->detalles as $detalle) {
                $material = MaterialModel::firstOrCreate(
                    ['codigo_material' => $detalle->codigo_material],
                    [
                        'nombre_material' => $detalle->descripcion_material,
                        'status' => 'active',
                    ],
                );

                // 3️⃣ Registrar materiales en orden de trabajo
                $this->registrarMaterialEnOT(
                    $pedido->orden_trabajo_id,
                    $material->id_material,
                    $detalle->cantidad
                );
            }

            DB::commit();

            return redirect()->route('solicitudes.show', $pedidoMaterialId)->with('success', 'Solicitud aprobada y materiales registrados correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            // Log para ti (backend)
            \Log::error('Error aprobando solicitud', [
                'pedido_id' => $pedidoMaterialId,
                'error' => $e->getMessage(),
            ]);

            // Mensaje claro al usuario
            return back()->with('error', 'Ocurrió un error al aprobar la solicitud. Intente nuevamente o contacte soporte.');
        }
    }

    // 🔹 Registrar material en orden de trabajo (desde solicitud aprobada)
    private function registrarMaterialEnOT(int $orderId, int $materialId, int $cantidad): void
    {
        $registro = WorkOrdersMaterialsModel::where('work_order_id', $orderId)
            ->where('material_id', $materialId)
            ->first();

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

    public function showSolicitud($pedidoMaterialId)
    {
        $pedido = PedidoMaterialModel::with([
            'detalles', // relación con detalle_solicitud_material
            'instalador',
            'proveedor',
        ])->findOrFail($pedidoMaterialId);

        return view('Solicitudes.show', compact('pedido'));
    }

    // 🔹 Guardar manualmente material desde el formulario
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
