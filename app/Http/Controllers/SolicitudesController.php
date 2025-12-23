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


    public function create($id){



        $ordenTrabajo = OrderWorkModel::with(['instalador', 'pedidosMateriales.instalador', 'pedidosMateriales.items'])->findOrFail($id);


        if ($ordenTrabajo == null ) {
            return redirect()->route('solicitudes.index')->with('error', 'Solicitud no encontrada');

        }


        return view('Solicitudes.crear', [
            'ordenTrabajo' => $ordenTrabajo
        ]);




    }

    // 🔹 Subir archivo Excel
    public function importExcel(Request $request, $id)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xls,xlsx|max:5120',
        ]);

        try {
            $file = $request->file('archivo_excel');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $proveedorNombre = null;
            $materiales = [];

            foreach ($rows as $index => $row) {
                // Limpia valores (por si hay celdas nulas)
                $row = array_map('trim', $row);

                // Detectar el proveedor
                if ($index === 0 && stripos($row[0], 'proveedor') !== false) {
                    $proveedorNombre = $row[1] ?? null;
                    continue;
                }

                // Saltar encabezado de columnas
                if (isset($row[0]) && strcasecmp($row[0], 'Código') === 0) {
                    continue;
                }

                // Detectar filas vacías
                if (empty($row[0]) || empty($row[1])) {
                    continue;
                }

                // Cada fila representa un material
                $codigo = $row[0];
                $cantidad = floatval($row[1]);
                $precio = floatval($row[2] ?? 0);
                $total = $cantidad * $precio;

                $materiales[] = [
                    'solicitud_material_id' => $id,
                    'codigo_material' => $codigo,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'total' => $total,
                    'fecha_registro' => now(),
                    'user_reg' => Auth::user()->id,
                ];
            }

            // Si se detectó proveedor, puedes actualizarlo en la solicitud:
            if ($proveedorNombre) {
                $this->dataSolicitudes->updateProveedorService($id, $proveedorNombre);
            }

            // Insertar los materiales
            foreach ($materiales as $material) {
                $this->dataSolicitudes->storeMaterialService($material);
            }

            return redirect()
                ->route('solicitudes.show', $id)
                ->with('success', 'Archivo importado correctamente. Proveedor: ' . ($proveedorNombre ?? 'No definido'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
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