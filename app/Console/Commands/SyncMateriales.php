<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Repository\OrderWorkRepository;

class SyncMateriales extends Command
{
    protected $signature = 'sync:materiales';
    protected $description = 'Sync materiales hacia Hostinger';

    public function handle(OrderWorkRepository $repo)
    {
        $this->info("Sync iniciado...");

        $chunkSize = 300; // balance óptimo

        $url = config('services.sync.url');
        $token = config('services.sync.token');

        if (!$url || !$token) {
            $this->error("SYNC_URL o SYNC_TOKEN no configurados");
            return Command::FAILURE;
        }
      
        $lastId = '';
        $total = 0;

    

        while (true) {

            $startTime = microtime(true); // ⏱ medir tiempo

            // 🔁 RETRY AUTOMÁTICO DEADLOCK
            $attempt = 0;

            while (true) {
                try {
                    $materials = $repo->getAllMaterials($lastId, $chunkSize);
                    break;

                } catch (\Illuminate\Database\QueryException $e) {

                    if (str_contains($e->getMessage(), '40001')) {
                        $attempt++;

                        if ($attempt >= 3) {
                            $this->error("Deadlock persistente en bloque, se omite");
                            Log::error('Deadlock persistente', ['lastId' => $lastId]);
                            continue 2;
                        }

                        $this->warn("Deadlock detectado, reintentando ($attempt/3)...");
                        sleep(2);

                    } else {
                        throw $e;
                    }
                }
            }

            if (empty($materials)) break;

            $data = [];

            foreach ($materials as $m) {

                $lastId = $m->codigo;

                $data[] = [
                    'codigo' => $m->codigo,
                    'nombre' => $m->nombre,
                    'ubicacion' => $m->ubicacion,
                    'saldo_inventario' => (float) $m->saldo_inventario,
                    'saldo_reservado' => (float) $m->saldo_reservado,
                    'imagen' => null
                ];
            }

            $this->info("Procesando desde ID: $lastId | Registros: " . count($materials));

            try {
                $response = Http::retry(3, 2000)
                    ->timeout(120)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $token
                    ])
                    ->post($url, ['data' => $data]);

                if (!$response->successful()) {
                    Log::error('SyncMateriales API error', [
                        'lastId' => $lastId,
                        'response' => $response->body()
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('SyncMateriales HTTP error', [
                    'lastId' => $lastId,
                    'error' => $e->getMessage()
                ]);
            }

            $total += count($materials);

            // ⏱ tiempo del bloque
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Tiempo bloque: {$duration}s");
        }

        $this->info("SYNC OK Total: $total");

        return Command::SUCCESS;
    }
}