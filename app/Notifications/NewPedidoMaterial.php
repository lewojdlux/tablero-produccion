<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;

class NewPedidoMaterial extends Notification implements ShouldBroadcast
{
    use Queueable;


    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Nueva solicitud de material',
            'message' => "Código: {$this->payload['material']['codigo']} | Cant: {$this->payload['material']['cantidad']}",
            'pedido_id' => $this->payload['pedido_id'],
            'material' => $this->payload['material'],
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => 'Nueva solicitud de material',
            'message' => "Código: {$this->payload['material']['codigo']} | Cant: {$this->payload['material']['cantidad']}",
            'pedido_id' => $this->payload['pedido_id'],
            'material' => $this->payload['material'],
            'created_at' => now()->toDateTimeString(),
        ]);
    }




}