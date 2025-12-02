<?php

namespace App\Livewire\User;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Badge extends Component
{
    public function render()
    {
        $user = Auth::user();
        $username = $user ? $user->username : 'Invitado';
        return view('livewire.user.badge', ['username' => $username]);
    }
}