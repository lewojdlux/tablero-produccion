<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



Schedule::command('sync:materiales')
    ->everyFiveMinutes()
    ->withoutOverlapping(10) // evita solapamientos por 10 min
    ->runInBackground() // no bloquea otros procesos
    
    ->appendOutputTo(storage_path('logs/sync_materiales.log')) // log persistente
    ->emailOutputOnFailure('sistemas1@dlux.com.co'); // alerta si falla