<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HGI\MaterialSyncService;
use App\Services\Integrations\WooCommerceService;

class SyncWooMateriales extends Command
{
    protected $signature = 'sync:woo-materiales';
    protected $description = 'Sincroniza stock de HGI a WooCommerce';

    public function handle(MaterialSyncService $service, WooCommerceService $woo)
    {
        $this->info("Iniciando...");
        $service->sync($woo);
        $this->info("Proceso enviado a la cola correctamente.");
        return Command::SUCCESS;
    }
}