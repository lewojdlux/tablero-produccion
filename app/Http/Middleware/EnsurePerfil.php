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

            // ADMIN y SUPER
            1, 2 => route('orders.pending.list'),

            // AUXILIAR (NO tienes ruta propia → usa la lista normal)
            3    => route('orders.pending.list'),

            // PRODUCCIÓN
            4    => route('orders.pending.production'),

            // ASESOR (NO tienes ruta propia → usa producción)
            5    => route('orders.pending.production'),

            // INSTALADOR (perfil 7)
            7    => route('ordenes.trabajo.asignados'),

            // Cualquier otro perfil desconocido
            default => route('orders.pending.list'),
        };
    }
}
