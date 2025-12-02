<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePerfil
{
    /**
     * Uso en rutas: ->middleware(['auth', 'perfil:1,2'])
     * Permite acceso si perfil_usuario_id ∈ {1,2,...}
     */
    public function handle(Request $request, Closure $next, ...$perfiles): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $permitidos = array_map('intval', $perfiles);
        if (!in_array((int) $user->perfil_usuario_id, $permitidos, true)) {
            return redirect()->to($this->routeForPerfil($user));
        }

        return $next($request);
    }

    protected function routeForPerfil($user): string
    {
        return match ((int) $user->perfil_usuario_id) {
            1, 2 => route('orders.pending.list'),            // Super/Admin
            3    => route('orders.pending.auxiliar'),   // Auxiliar
            4, 5    => route('orders.pending.production'), // Producción
           // 5    => route('orders.pending.asesor'),     // Asesor
            default => route('orders.pending'),
        };
    }
}