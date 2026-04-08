<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\OrdenTrabajoFinalizada;
use App\Models\User;
use App\Notifications\OrdenTrabajoFinalizadaNotification;



class EnviarNotificacionOTFinalizada
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrdenTrabajoFinalizada $event)
    {
        $orden = $event->orden;

        $asesor = User::where(
            'identificador_asesor',
            $orden->codigo_asesor
        )->first();

        if ($asesor) {

            $asesor->notify(
                new OrdenTrabajoFinalizadaNotification($orden)
            );
        }
    }
}
