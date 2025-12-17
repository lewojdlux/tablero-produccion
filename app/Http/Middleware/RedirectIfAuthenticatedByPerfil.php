<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticatedByPerfil
{
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        if ($user = $request->user()) {
            return redirect()->to(match ((int) $user->perfil_usuario_id) {

                // ADMIN / SUPER
                1, 2 => route('orders.pending.list'),

                // AUXILIAR (perfil 3) → NO tienes ruta, así que usaré la misma lista admin
                3    => route('orders.pending.list'),

                // PRODUCCIÓN (perfil 4)
                4    => route('orders.pending.production'),

                // ASESOR (perfil 5) → NO tienes ruta, usaré producción
                5    => route('orders.pending.production'),

                // INSTALADOR (perfil 7)
               7    => route('ordenes.trabajo.asignados'),

                default => route('orders.pending.list'),
            });
        }

        return $next($request);
    }
}
