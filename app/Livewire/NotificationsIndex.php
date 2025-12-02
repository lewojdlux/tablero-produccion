<?php

namespace App\Livewire;


use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;



use Illuminate\Support\Facades\Auth;

class NotificationsIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;
    public string $tab = 'unread'; // 'unread' or 'all' or 'read'
    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'refreshNotifications' => '$refresh'
    ];

    public function mount()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function setTab(string $tab)
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function markAsRead(string $id)
    {
        $user = Auth::user();
        if (! $user) return;

        $notif = $user->notifications()->where('id', $id)->first();
        if ($notif && is_null($notif->read_at)) {
            $notif->markAsRead();
        }

        $this->emit('refreshNotifications');
    }

    public function openNotification(string $id)
    {
        $user = Auth::user();
        if (! $user) return;

        $notif = $user->notifications()->where('id', $id)->first();
        if (! $notif) return;

        // marcar leído
        if (is_null($notif->read_at)) {
            $notif->markAsRead();
        }

        $url = $notif->data['url'] ?? null;

        // recargar lista (si no redirigimos)
        $this->emit('refreshNotifications');

        if ($url) {
            // retorna redirect — Livewire hará la redirección
            return redirect()->to($url);
        }

        session()->flash('message', 'Notificación marcada como leída.');
    }

    public function markAllRead()
    {
        $user = Auth::user();
        if (! $user) return;

        $user->unreadNotifications()->update(['read_at' => now()]);
        $this->emit('refreshNotifications');
    }

    public function deleteNotification(string $id)
    {
        $user = Auth::user();
        if (! $user) return;

        $notif = $user->notifications()->where('id', $id)->first();
        if ($notif) {
            $notif->delete();
        }
        $this->emit('refreshNotifications');
    }


    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();
        if (! $user) {
            return view('livewire.notifications-index', ['notifications' => collect([])]);
        }

        $query = $user->notifications()->orderBy('created_at', 'desc');

        if ($this->tab === 'unread') {
            $query = $user->unreadNotifications()->orderBy('created_at', 'desc');
        } elseif ($this->tab === 'read') {
            $query = $user->readNotifications()->orderBy('created_at', 'desc');
        } // else 'all' -> keep $query (all notifications)

        $notifications = $query->paginate($this->perPage);

        return view('livewire.notifications-index', [
            'notifications' => $notifications
        ]);
    }
}