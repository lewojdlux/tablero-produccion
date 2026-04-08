<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrdenTrabajoProgramada implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $mensaje;
    public $nDocumento;
    public $fechaProgramada;
    public $vendedorId;

    public function __construct($mensaje, $nDocumento, $fechaProgramada, $vendedorId)
    {
        $this->mensaje = $mensaje;
        $this->nDocumento = $nDocumento;
        $this->fechaProgramada = $fechaProgramada;
        $this->vendedorId = $vendedorId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('vendedor.' . $this->vendedorId);
    }

    public function broadcastAs()
    {
        return 'ot.programada';
    }

    public function broadcastWith()
    {
        return [
            'type' => 'info',
            'title' => 'Orden de trabajo programada',
            'message' => $this->mensaje,
            'nDocumento' => $this->nDocumento,
            'fechaProgramada' => $this->fechaProgramada,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
