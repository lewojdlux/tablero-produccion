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

    public function create($id)
    {
        $ordenTrabajo = OrderWorkModel::with(['instalador', 'pedidosMateriales.instalador', 'pedidosMateriales.items'])->findOrFail($id);

        if ($ordenTrabajo == null) {
            return redirect()->route('solicitudes.index')->with('error', 'Solicitud no encontrada');
        }

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

        // 🔹 1. Crear o buscar pedido_material (PADRE)
        $pedidoMaterial = PedidoMaterialModel::firstOrCreate(
            ['orden_trabajo_id' => $id],
            [
                'instalador_id' => $request->instalador_id ?? null,
                'status' => 'queued',
                'fecha_solicitud' => now(),
            ],
        );

        $pedidoMaterialId = $pedidoMaterial->id_pedido_material;

        // 🔹 2. Leer Excel
        $spreadsheet = IOFactory::load($request->file('archivo_excel')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $proveedorNombre = null;
        $ivaGeneral = 0;
        $materiales = [];

        foreach ($rows as $row) {
            $row = array_map(fn($v) => trim((string) $v), $row);

            // 🔹 Proveedor
            if (strcasecmp($row['A'], 'proveedor') === 0) {
                $proveedorNombre = $row['B'] ?? null;
                $ivaGeneral = is_numeric($row['E']) ? (float) $row['E'] : 0;
                continue;
            }

            // 🔹 Ignorar encabezados
            if (empty($row['A']) || in_array(strtolower($row['A']), ['código', 'codigo', 'col a'])) {
                continue;
            }

            $cantidad = is_numeric($row['C']) ? (float) $row['C'] : 0;
            $precio = is_numeric($row['D']) ? (float) $row['D'] : 0;

            $materiales[] = [
                'solicitud_material_id' => $pedidoMaterialId, // ✅ ID CORRECTO
                'codigo_material' => $row['A'],
                'descripcion_material' => $row['B'] ?? '',
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'iva' => is_numeric($row['E']) ? (float) $row['E'] : 0,
                'iva_porcentaje' => $ivaGeneral,
                'descuento' => 0,
                'total' => $cantidad * $precio,
                'fecha_registro' => now(),
                'user_reg' => Auth::id(),
            ];
        }

        // 🔹 3. Proveedor
        if (!$proveedorNombre) {
            DB::rollBack();
            return back()->with('error', 'El archivo no contiene el proveedor.');
        }

        $proveedor = ProveedorModel::where('name_supplier', $proveedorNombre)->first();
        if (!$proveedor) {
            DB::rollBack();
            return back()->with('error', 'El proveedor "' . $proveedorNombre . '" no existe.');
        }

        $pedidoMaterial->update(['proveedor_id' => $proveedor->id_supplier]);

        // 🔹 4. Insertar detalles
        foreach ($materiales as $material) {
            DetalleSolicitudMaterialModel::updateOrCreate(
                [
                    // CLAVE ÚNICA DE NEGOCIO
                    'solicitud_material_id' => $material['solicitud_material_id'],
                    'codigo_material' => $material['codigo_material'],
                ],
                [
                    // DATOS A GUARDAR / ACTUALIZAR
                    'descripcion_material' => $material['descripcion_material'],
                    'cantidad' => $material['cantidad'],
                    'precio_unitario' => $material['precio_unitario'],
                    'iva' => $material['iva'],
                    'iva_porcentaje' => $material['iva_porcentaje'],
                    'descuento' => 0,
                    'total' => $material['total'],
                    'fecha_registro' => now(),
                    'user_reg' => Auth::id(),
                ]
            );
        }

        DB::commit();

        return redirect()->route('solicitudes.show', $pedidoMaterialId)->with('success', 'Solicitud de material importada correctamente.');
    }


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

                MaterialModel::firstOrCreate(
                    ['codigo_material' => $detalle->codigo_material],
                    [
                        'nombre_material' => $detalle->descripcion_material,
                        'status' => 'active',
                    ]
                );
            }

            DB::commit();

            return redirect()->route('solicitudes.show', $pedidoMaterialId)->with('success', 'Solicitud aprobada y materiales registrados correctamente.');

        } catch (\Throwable $e) {

            DB::rollBack();

            // Log para ti (backend)
            \Log::error('Error aprobando solicitud', [
                'pedido_id' => $pedidoMaterialId,
                'error' => $e->getMessage()
            ]);

            // Mensaje claro al usuario
            return back()->with('error', 'Ocurrió un error al aprobar la solicitud. Intente nuevamente o contacte soporte.');
        }
    }


    public function showSolicitud($pedidoMaterialId)
    {
        $pedido = PedidoMaterialModel::with([
            'detalles',   // relación con detalle_solicitud_material
            'instalador',
            'proveedor'
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
