<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

use Illuminate\Support\Facades\Event;
use App\Events\OrdenTrabajoFinalizada;
use App\Listeners\EnviarNotificacionOTFinalizada;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Event::listen(
            OrdenTrabajoFinalizada::class,
            EnviarNotificacionOTFinalizada::class
        );

    }
}