<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewPedidoMaterial extends Notification
{
    use Queueable;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function via($notifiable)
    {
        return ['database']; // usaremos la tabla notifications para polling
    }

    public function toDatabase($notifiable)
    {
        return $this->payload;
    }
}