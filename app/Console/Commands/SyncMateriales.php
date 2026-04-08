<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Repository\OrderWorkRepository;
use Illuminate\Http\Client\Response;

class SyncMateriales extends Command
{
    protected $signature = 'sync:materiales';
    protected $description = 'Sync materiales hacia Hostinger';

    public function handle(OrderWorkRepository $repo)
    {
        $this->info("Sync optimizado iniciado...");

    $chunkSize = 500;
    $start = 1;
    $totalProcesados = 0;

    while (true) {

        $end = $start + $chunkSize - 1;

        $materials = $repo->getAllMaterials($start, $end);

        if (empty($materials)) {
            break;
        }

        $this->info("Procesando registros $start - $end");

        $data = [];

        foreach ($materials as $m) {
            $data[] = [
                'codigo' => $m->codigo,
                'nombre' => $m->nombre,
                'ubicacion' => $m->ubicacion,
                'saldo_inventario' => $m->saldo_inventario,
                'saldo_reservado' => $m->saldo_reservado,
                'saldo_disponible' => $m->saldo_disponible,
                'imagen' => null // 🔥 NO IMÁGENES
            ];
        }

        try {

            Http::withHeaders([
                'Authorization' => 'Bearer ' . env('SYNC_TOKEN')
            ])
            ->timeout(120)
            ->post(env('SYNC_URL'), [
                'data' => $data
            ]);

        } catch (\Exception $e) {
            $this->error("Error HTTP: " . $e->getMessage());
            return;
        }

        $totalProcesados += count($materials);
        $start += $chunkSize;
    }

    $this->info("SYNC COMPLETO ✅ Total: $totalProcesados");
    }
}
