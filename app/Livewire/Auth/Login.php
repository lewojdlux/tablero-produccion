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
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]; // usa el layout de abajo

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.auth.login');
    }

    public function login()
    {
        $this->validate();

        // Rate limit
        $key = Str::lower($this->email) . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Demasiados intentos. Intenta en {$seconds} segundos.");
            return;
        }

        // 1) Buscar usuario
        $user = \App\Models\User::where('email', $this->email)->first();

        // 2) Usuario inactivo
        if ($user && !(bool) ($user->estado ?? false)) {
            $this->addError('email', 'Tu cuenta está inactiva. Habla con un administrador.');
            return;
        }

        // 3) Intento de login
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            request()->session()->regenerate();
            RateLimiter::clear($key);

            $user = Auth::user();

            /* ==============================
           REDIRECCIÓN SEGÚN PERFIL
        ============================== */

            // Admin / Super
            if (in_array($user->perfil_usuario_id, [1, 2])) {
                return redirect()->intended(route('orders.pending.list'));
            }

            // Auxiliar (NO tienes ruta → usar la lista normal)
            if ($user->perfil_usuario_id == 3) {
                return redirect()->intended(route('orders.pending.list'));
            }

            // Producción
            if ($user->perfil_usuario_id == 4) {
                return redirect()->intended(route('orders.pending.production'));
            }

            // Asesor (NO tienes ruta propia → producción)
            if ($user->perfil_usuario_id == 5) {
                return redirect()->intended(route('orders.pending.production'));
            }

            // Instalador
            if ($user->perfil_usuario_id == 7) {
                return redirect()->intended(route('ordenes.trabajo.asignados'));
            }
        }

        RateLimiter::hit($key, 60);
        $this->addError('email', 'Las credenciales no coinciden con nuestros registros.');
    }
}
