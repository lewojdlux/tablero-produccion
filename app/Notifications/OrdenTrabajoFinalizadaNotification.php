<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrdenTrabajoFinalizadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $orden;

    public function __construct($orden)
    {
        $this->orden = $orden;
        $this->onQueue('notifications');
    }

    public function via($notifiable)
    {
        return ['mail','database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Orden de trabajo finalizada')
            ->greeting(' ')
            ->line('Se ha finalizado una orden de trabajo en el sistema.')

            ->line('**Detalles de la orden:**')
            ->line('Número OT: #' . $this->orden->n_documento)
            ->line('Cliente: ' . $this->orden->tercero)
            ->line('Fecha de finalización: ' . now()->format('d/m/Y H:i'))

            ->action('Ver orden de trabajo', url('/ordenes-trabajo/asignados'))

            ->line('Puede ingresar al sistema para consultar todos los detalles de la orden.')
            ->salutation(' ')
            ->line('Equipo D-LUX');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'OT Finalizada',
            'message' => 'La OT #' . $this->orden->n_documento . ' fue finalizada.',
            'work_order_id' => $this->orden->id_work_order
        ];
    }
}