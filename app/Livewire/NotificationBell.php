<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;


use Illuminate\Support\Facades\Auth;


class NotificationBell extends Component
{


    public array $unread = [];
    public int $count = 0;
    public int $pollIntervalMs = 60000; // polling en ms (ej: 60000 = 1min)

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->unread = [];
            $this->count = 0;
            return;
        }

        $notifs = $user->unreadNotifications()->orderBy('created_at', 'desc')->take(10)->get();

        // Normalizamos: añadimos category (si no tiene, ponemos 'general')
        $items = $notifs->map(function ($n) {
            $data = (array) ($n->data ?? []);
            return [
                'id' => $n->id,
                'type' => $data['category'] ?? ($data['tipo'] ?? 'general'),
                'title' => $data['title'] ?? $data['descripcion'] ?? 'Nueva notificación',
                'subtitle' => $data['subtitle'] ?? null,
                'orden_trabajo_id' => $data['orden_trabajo_id'] ?? null,
                'url' => $data['url'] ?? null,
                'created_at' => $n->created_at,
                'ago' => $n->created_at->diffForHumans(),
                'raw' => $data,
            ];
        })->toArray();

        $this->unread = $items;
        $this->count = count($items);
    }

    /**
     * Marca una notificación como leída y redirige si trae url.
     */
    public function markAsReadAndGo(string $id = null)
    {
        $user = Auth::user();
        if (!$user || !$id) {
            $this->loadNotifications();
            return;
        }

        $notif = $user->unreadNotifications()->where('id', $id)->first();
        if ($notif) {
            $notif->markAsRead();
            $url = $notif->data['url'] ?? null;
            $this->loadNotifications();

            if ($url) {
                // Livewire redirect (funciona en navegador)
                return $this->redirect($url);
            }
        }

        $this->loadNotifications();
    }

    /**
     * Marca todas las notificaciones como leídas.
     */
    public function markAllRead(): void
    {
        $user = Auth::user();
        if (!$user) return;

        // Eficiente: actualiza campo read_at en DB
        $user->unreadNotifications()->update(['read_at' => now()]);

        $this->loadNotifications();
    }


    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.notification-bell');
    }
}