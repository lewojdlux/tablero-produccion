<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaterialSolicitadoEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    public function __construct($payload)
    {
        $this->data = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('admin-channel');
    }

    public function broadcastAs()
    {
        return 'material.solicitado';
    }

    public function broadcastWith()
    {
        return $this->data;
    }
}