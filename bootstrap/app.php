<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\EnsurePerfil;
use App\Http\Middleware\RedirectIfAuthenticatedByPerfil;


use Illuminate\Broadcasting\BroadcastServiceProvider;
use App\Providers\BroadcastServiceProvider as AppBroadcastServiceProvider;

return Application::configure(basePath: dirname(__DIR__))

    ->withProviders([
        BroadcastServiceProvider::class,      // Provider de Laravel
        AppBroadcastServiceProvider::class,   // Tu provider
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
         $middleware->alias([
            'perfil' => EnsurePerfil::class,
            'redirect.perfil' => RedirectIfAuthenticatedByPerfil::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();