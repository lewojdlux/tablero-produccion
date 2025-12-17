<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registra las rutas /broadcasting/auth
        Broadcast::routes(['middleware' => ['auth']]);

        // Carga el archivo routes/channels.php
        require base_path('routes/channels.php');
    }
}