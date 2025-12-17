<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaterialSolicitadoNotification extends Notification
{
    use Queueable;

    protected $pedido;
    protected $item;

    /**
     * Create a new notification instance.
     */
    public function __construct($pedido, $item)
    {
        $this->pedido = $pedido;
        $this->item   = $item;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('Nueva solicitud de material.')
                    ->action('Ver solicitud', url('/pedidos-materiales/'.$this->pedido->id_pedido_material))
                    ->line("Se solicitó el material {$this->item->descripcion_material} (cant: {$this->item->cantidad})");
    }



    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Nueva solicitud de material',
            'message' => "Se solicitó el material {$this->item->descripcion_material} (cant: {$this->item->cantidad})",
            'pedido_id' => $this->pedido->id_pedido_material,
            'orden_trabajo_id' => $this->pedido->orden_trabajo_id,
            'instalador_id' => $this->pedido->instalador_id,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
