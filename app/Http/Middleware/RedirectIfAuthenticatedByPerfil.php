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
                1, 2 => route('orders.pending'),
                3    => route('orders.pending.auxiliar'),
                4    => route('orders.pending.production'),
                5    => route('orders.pending.asesor'),
                default => route('orders.pending'),
            });
        }

        return $next($request);
    }
}
