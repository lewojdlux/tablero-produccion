<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Mail\Mailables\Address;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductionStatusChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $orderId,
        public int $nproduccion,
        public int $pedido,
        public string $status,
        public ?string $observacion,
        public ?string $fechaInicial,
        public ?string $horaInicial,
        public ?string $fechaFinal,
        public ?string $horaFinal,
        public ?int $dias,
        public ?int $horas,
        public ?int $mins,
        public ?int $segs,
        public ?int $cantidadLuminarias,
        public ?string $luminariaNombre, // si lo tienes
        public ?string $cambioPor // nombre/usuario que ejecutó el cambio
    )
    {
        //

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function statusLabel(): string
    {
        return match ($this->status) {
            'queued'       => 'En cola',
            'in_progress'  => 'En producción',
            'done'         => 'Terminado',
            'approved'     => 'Aprobado',
            default        => ucfirst(str_replace('_',' ',$this->status)),
        };
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {


    $fromAddress = config('mail.from.address') ?: 'info@dlux.com.co';
    $fromName    = config('mail.from.name')    ?: config('app.name', 'DLUX');

    $duracion = [];
    if ($this->dias !== null)  $duracion[] = "{$this->dias}d";
    if ($this->horas !== null) $duracion[] = "{$this->horas}h";
    if ($this->mins !== null)  $duracion[] = "{$this->mins}m";
    if ($this->segs !== null)  $duracion[] = "{$this->segs}s";
    $duracionStr = $duracion ? implode(' ', $duracion) : '—';

    $fi = $this->fechaInicial ? date('d/m/Y', strtotime($this->fechaInicial)) : '—';
    $hi = $this->horaInicial ?: '—';
    $ff = $this->fechaFinal   ? date('d/m/Y', strtotime($this->fechaFinal))   : '—';
    $hf = $this->horaFinal ?: '—';

    $luminaria = $this->luminariaNombre ?: '—';
    $cant      = $this->cantidadLuminarias !== null ? (string)$this->cantidadLuminarias : '—';

    $url = route('orders.production.show', ['order' => $this->orderId]);

    $mail = (new MailMessage)
            ->mailer('smtp') // opcional, fuerza el mailer 'smtp'
            ->from($fromAddress, $fromName) // ✅ strings, no Address
            ->subject("Orden #{$this->nproduccion} — Estado: ".$this->statusLabel())
            ->greeting('Hola,')
            ->line("La orden de producción **#{$this->nproduccion} del pedido {$this->pedido}** ha cambiado al estado **{$this->statusLabel()}**.")
            ->line("• Luminaria: **{$luminaria}**")
            ->line("• Cantidad a producir: **{$cant}**")
            ->line("• Inicio: **{$fi} {$hi}**")
            ->line("• Fin: **{$ff} {$hf}**")
            ->line("• Duración total: **{$duracionStr}**");

        if (!empty($this->observacion)) {
            $obs = $this->cleanObservation($this->observacion);

            $mail->line('• Observación:')
                ->line($obs); // sin comillas
        }

        if (!empty($this->cambioPor)) {
            $mail->line("Actualizado por: **{$this->cambioPor}**");
        }

        $mail->action('Ver en el sistema',  $url)
             ->line('Este es un mensaje automático.');

        return $mail;
    }


    private function cleanObservation(?string $text): string
    {
        if ($text === null) return '';
        // recorta espacios y remueve comillas iniciales/finales (", ', “ ”, ‘ ’)
        $clean = trim($text);
        $clean = preg_replace('/^[\'"“”‘’\s]+/u', '', $clean);
        $clean = preg_replace('/[\'"“”‘’\s]+$/u', '', $clean);

        return $clean;
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