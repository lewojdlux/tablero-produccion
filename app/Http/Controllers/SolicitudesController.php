<?php

namespace App\Http\Controllers;

use App\Models\InstaladorModel;
use App\Models\OrderWorkModel;
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

        $spreadsheet = IOFactory::load($request->file('archivo_excel')->getRealPath());

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $proveedorNombre = null;
        $ivaGeneral = null;
        $materiales = [];

        foreach ($rows as $rowIndex => $row) {
            $row = array_map(fn($v) => trim((string) $v), $row);

            /**
             * FILA 1
             * proveedor | Juan Osorio | iva | | 19
             */
            if ($rowIndex === 1 && strcasecmp($row['A'], 'proveedor') === 0) {
                $proveedorNombre = $row['B'] ?? null;
                $ivaGeneral = (float) ($row['E'] ?? 0);
                continue;
            }

            /**
             * FILA 3
             * Encabezados
             */
            if ($rowIndex === 3) {
                continue;
            }

            /**
             * DATOS DESDE FILA 4
             */
            if ($rowIndex < 4 || empty($row['A'])) {
                continue;
            }

            $cantidad = (float) $row['C'];
            $precio = (float) $row['D'];

            $materiales[] = [
                'solicitud_material_id' => $id,
                'codigo_material' => $row['A'],
                'descripcion_material' => $row['B'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'iva' => (float) $row['E'],
                'iva_porcentaje' => $ivaGeneral,
                'descuento' => 0,
                'total' => $cantidad * $precio,
                'fecha_registro' => now(),
                'user_reg' => Auth::id(),
            ];
        }

        /**
         * PROVEEDOR
         * SOLO SE ASOCIA, NO SE CREA
         */
        if ($proveedorNombre) {
            $proveedor = ProveedorModel::where('name_supplier', $proveedorNombre)->first();

            if (!$proveedor) {
                DB::rollBack();
                return redirect()
                    ->back()
                    ->with('error', 'El proveedor "' . $proveedorNombre . '" no existe. Debe crearlo antes.');
            }

            $this->dataSolicitudes->updateProveedorService($id, $proveedor->id_supplier);
        }

        /**
         * INSERTAR DETALLES
         */
        foreach ($materiales as $material) {
            $this->dataSolicitudes->storeMaterialService($material);
        }

        DB::commit();

        return redirect()->route('solicitudes.show', $id)->with('success', 'Solicitud importada correctamente.');
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