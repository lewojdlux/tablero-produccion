<?php

namespace App\Livewire\Auth;


use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected array $rules = [
        'email' => ['required','string','email'],
        'password' => ['required','string'],
    ];

    #[Layout('layouts.guest')] // usa el layout de abajo
    public function render()
    {
        return view('livewire.auth.login');
    }

    public function login()
    {
        $this->validate();

        // Rate limit (5 intentos por minuto por email+IP)
        $key = Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', __('Too many attempts. Try again in :s seconds.', ['s' => $seconds]));
            return;
        }

        // 1) Buscar usuario por email
        $user = \App\Models\User::where('email', $this->email)->first();

        // 2) Si existe pero está inactivo, mensaje y NO contamos intento
        if ($user && !(bool)($user->estado ?? false)) {
            $this->addError('email', 'Tu cuenta está inactiva. Habla con un administrador.');
            return;
        }


         // 3) Intento de login normal
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            request()->session()->regenerate();
            RateLimiter::clear($key);

            $user = Auth::user();

            // Redirigir según perfil de usuario

            if ($user->perfil_usuario_id == 2 || $user->perfil_usuario_id == 1) { // Admin - // Super Admin
                return redirect()->intended(route('orders.pending.list'));
            }

            if ($user->perfil_usuario_id == 3) { // Auxiliar
                return redirect()->intended(route('orders.pending.auxiliar'));
            }

            if ($user->perfil_usuario_id == 4) { // Producción
                return redirect()->intended(route('orders.pending.production'));
            }

            if ($user->perfil_usuario_id == 5) { // Asesor Comercial
                return redirect()->intended(route('orders.pending.production'));
            }

        }

        RateLimiter::hit($key, 60); // penaliza 60s por intento fallido
        $this->addError('email', __('These credentials do not match our records.'));
    }


}