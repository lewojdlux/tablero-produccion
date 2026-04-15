<?php

namespace App\Services\HGI;

use App\Repository\HGI\MaterialRepository;
use App\Services\Integrations\WooCommerceService;
use Illuminate\Support\Facades\Log;

class MaterialSyncService
{
    protected $repo;

    public function __construct(MaterialRepository $repo)
    {
        $this->repo = $repo;
    }

    public function sync(WooCommerceService $woo)
    {
        $lastId = '';
        $chunkSize = 100;

        // 🔥 cache en memoria
        $notFoundCache = [];

        while (true) {

            $materials = $this->repo->getAllMaterials($lastId, $chunkSize);
            if (empty($materials)) {
                break;
            }

            foreach ($materials as $m) {

                $sku = trim((string) $m->codigo);
                if ($sku === '' || $sku === '0') {
                    continue;
                }

                $lastId = $sku;

                // 🔥 evitar repetir fallos
                if (isset($notFoundCache[$sku])) {
                    continue;
                }

                $stock = (float) $m->saldo_inventario - (float) $m->saldo_reservado;
                $stockFinal = max(0, (int) $stock);

                try {

                    $wooData = $woo->findProductRealBySku($sku);

                    if (! $wooData) {
                        $notFoundCache[$sku] = true;

                        Log::warning('NO EXISTE EN WOO', ['sku' => $sku]);

                        continue;
                    }

                    $res = $woo->updateStock($wooData, $stockFinal);

                    if (! $res || ! $res->successful()) {
                        Log::error('ERROR UPDATE', [
                            'sku' => $sku,
                            'status' => $res?->status(),
                            'body' => $res?->body(),
                        ]);
                    } else {
                        Log::info('ACTUALIZADO', [
                            'sku' => $sku,
                            'stock' => $stockFinal,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('ERROR GENERAL SKU', [
                        'sku' => $sku,
                        'error' => $e->getMessage(),
                    ]);
                }

                usleep(200000); // 🔥 no saturar Woo
            }
        }
    }
}
