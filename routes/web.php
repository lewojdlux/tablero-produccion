<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


Route::middleware('guest')->group(function () {
    Route::get('/login', \App\Livewire\Auth\Login::class)->name('login');
});

// ===== Logout GLOBAL (una sola vez, accesible para TODOS los autenticados) =====
Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return to_route('login');
})->middleware('auth')->name('logout');


/**
 * ADMIN / SUPER (perfiles 1,2)
 * Prefijo y alias: admin.*
 */
Route::middleware(['auth', 'perfil:1,2'])
    ->group(function () {


          // Dashboard admin
        Route::get('/',  \App\Livewire\Order\PendingList::class)
            ->name('dashboard');


        // Órdenes pendientes (admin/super)
        Route::get('/orders/pending', \App\Livewire\Order\PendingList::class)
            ->name('orders.pending.list');


        // Gestión de usuarios (admin/super)
        Route::get('/users', \App\Livewire\User\Index::class)
            ->name('users.index');

        // Settings (perfil admin/super; muévelas si deben ser globales)
        Route::get('/settings/profile', \App\Livewire\User\Profile::class)
            ->name('settings.profile');

        Route::get('/settings/password', \App\Livewire\User\Password::class)
            ->name('settings.password');
});



/**
 * PRODUCCIÓN (perfil 4) y Asesor (perfil 5)
 * Prefijo y alias: production.*
 */
Route::middleware(['auth', 'perfil:4,5'])
    ->group(function () {



        // Órdenes pendientes de producción
        Route::get('/orders/pending/production', \App\Livewire\Order\PendingProduction::class)
            ->name('orders.pending.production');

        Route::get('/orders/production/{order}',\App\Livewire\Order\PendingProduction::class)
        ->name('orders.production.show');



        // Settings (perfil admin/super; muévelas si deben ser globales)
        /*Route::get('/settings/profile', \App\Livewire\User\Profile::class)
            ->name('settings.profile');

        Route::get('/settings/password', \App\Livewire\User\Password::class)
            ->name('settings.password');*/
});


Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return to_route('login');
})->middleware(['web','auth'])->name('logout');
