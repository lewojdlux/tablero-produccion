<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sync:materiales')
            ->everyFiveMinutes()
            ->withoutOverlapping(10) // evita solapamientos por 10 min
            ->runInBackground() // no bloquea otros procesos
            
            ->appendOutputTo(storage_path('logs/sync_materiales.log')) // log persistente
            ->emailOutputOnFailure('sistemas1@dlux.com.co'); // alerta si falla
    }
}