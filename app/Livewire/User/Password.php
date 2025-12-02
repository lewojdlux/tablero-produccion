<?php

namespace App\Livewire\User;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Password extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'current_password' => ['required','current_password'],
            'password' => ['required','string','min:8','confirmed'],
        ];
    }

    public function updatePassword()
    {
        $this->validate();

        $u = Auth::user();
        // Tu modelo tiene cast 'password' => 'hashed', así que asigna directo:
        $u->password = $this->password;
        $u->save();

        $this->reset(['current_password','password','password_confirmation']);
        $this->dispatch('password-updated');
        session()->flash('status', 'password-updated');
    }

    #[Layout('layouts.app')]
    #[Title('Contraseña')]
    public function render()
    {
        return view('livewire.user.password');
    }
}
