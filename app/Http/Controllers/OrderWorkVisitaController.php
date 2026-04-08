<?php

namespace App\Http\Controllers;


use App\Models\OrderWorkModel;
use App\Models\OrderWorkVisitaModel;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class OrderWorkVisitaController
{
    //
    public function view($id)
    {
        Log::info('Entrando a view visitas', ['id' => $id]);

        try {
            $orden = OrderWorkModel::where('id_work_order', $id)->firstOrFail();

            Log::info('Orden encontrada', ['orden' => $orden]);

            return view('workorders.visitas', ['orden' => $orden]);

        } catch (\Exception $e) {

            Log::error('Error en view visitas', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error cargando vista',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function index($orderId)
    {
        $visitas = OrderWorkVisitaModel::where('order_work_id', $orderId)
            ->orderBy('fecha_visita', 'desc')
            ->get();

        return response()->json($visitas);
    }

    

    public function store(Request $request, $orderId)
    {
        try {

            $request->validate([
                'fecha_visita' => 'required|date',
                'observacion' => 'nullable|string|max:1000',
            ]);

            OrderWorkVisitaModel::create([
                'order_work_id' => $orderId,
                'fecha_visita' => $request->fecha_visita,
                'observacion' => $request->observacion,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Visita registrada correctamente'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar visita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    

    public function destroy($id)
    {
        try {

            $visita = OrderWorkVisitaModel::findOrFail($id);
            $visita->delete();

            return response()->json([
                'success' => true,
                'message' => 'Visita eliminada'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar visita',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
