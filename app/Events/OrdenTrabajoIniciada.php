<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrdenTrabajoIniciada implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $mensaje,
        public readonly string $nDocumento,
        public readonly int    $vendedorId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vendedor.' . $this->vendedorId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ot.iniciada';
    }
}
