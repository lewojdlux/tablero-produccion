<?php

namespace App\Jobs\Sync;

use App\Services\Integrations\WooCommerceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWooBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;

    public function __construct($batch)
    {
        $this->batch = $batch;
    }

    public function handle(WooCommerceService $woo)
    {
        foreach ($this->batch as $item) {

            $res = $woo->updateStock($item['woo_info'], $item['stock']);

            if ($res && $res->successful()) {
                Log::channel('sync_woo')->info("ACTUALIZADO: SKU {$item['sku']} con stock {$item['stock']}");
            } else {
                Log::channel('sync_woo')->error("ERROR ACTUALIZANDO {$item['sku']}");
            }
        }
    }
}
