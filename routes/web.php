<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Broadcast;


use App\Http\Controllers\OrdenesTrabajoController;
use App\Http\Controllers\AsignarMaterialController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\SeguimientoCrmController;



Broadcast::routes([
    'middleware' => ['auth']
]);


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


        Route::get('/ordenes-trabajo/asignar', [OrdenesTrabajoController::class, 'index'])->name('ordenes.trabajo.asignar');
        Route::post('/ordenes-trabajo/asignar', [OrdenesTrabajoController::class, 'store'])->name('ordenes.trabajo.store');
        Route::get('/pedidos-materiales/{pedido}', [OrdenesTrabajoController::class, 'show'])->name('pedidos.materiales.show');
        Route::get('/ordenes-trabajo/crear', [OrdenesTrabajoController::class, 'create'])->name('workorders.create');


        Route::get('/ordenes-trabajo/asignados', [OrdenesTrabajoController::class, 'indexAsignados'])->name('ordenes.trabajo.asignadas');




        Route::get('/pedidos-materiales/orden-trabajo/{orderId}', [OrdenesTrabajoController::class, 'verPedidoMaterial'])->name('pedidos.materiales.byOrden');

        Route::get('/ordenes-trabajo/{workorder}', [OrdenesTrabajoController::class, 'verOrden'])->name('workorders.show');

        Route::get('/notificaciones', function () {
            $notificaciones = auth()->user()
                ->notifications()
                ->latest()
                ->paginate(20);

            return view('notificaciones.index', compact('notificaciones'));
        })->name('notificaciones.index');



        Route::get('/notificaciones/{notification}', function ($notification) {

            $n = auth()->user()
                ->notifications()
                ->where('id', $notification)
                ->firstOrFail();

            if (is_null($n->read_at)) {
                $n->markAsRead();
            }

            // Redirigir al pedido si existe
            if (isset($n->data['pedido_id'])) {
                return redirect()->route('pedidos.materiales.show', $n->data['pedido_id']);
            }

            return redirect()->route('notificaciones.index');

        })->name('notificaciones.leer');



        Route::middleware(['auth', 'perfil:1,2,5'])
        ->prefix('crm')
        ->group(function () {

            // Vista principal CRM
            Route::get('/seguimiento', [SeguimientoCrmController::class, 'index'])
                ->name('portal-crm.seguimiento.index');

            // Data AJAX CRM
            Route::get('/seguimiento/data', [SeguimientoCrmController::class, 'data'])
                ->name('portal-crm.seguimiento.data');
        });


        Route::get('/ordenes-trabajo/solicitudes', [SolicitudesController::class, 'solicitudes'])->name('solicitudes.index');
        Route::get('/ordenes-trabajo/solicitudes/crear/{id}', [SolicitudesController::class, 'create'])->name('solicitudes.create');
        Route::post('/ordenes-trabajo/solicitudes/registrar', [SolicitudesController::class, 'store'])->name('solicitudes.store');





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



Route::middleware(['auth', 'perfil:7'])
    ->group(function () {

    Route::get('/ordenes/trabajo/asignados', [OrdenesTrabajoController::class, 'indexAsignados'])->name('ordenes.trabajo.asignados');
    Route::post('/ordenes-trabajo/{workorder}/start', [OrdenesTrabajoController::class, 'start'])->name('workorders.start');
    Route::get('/ordenes-trabajo/{id}/materials',[OrdenesTrabajoController::class, 'indexMaterialesOrdenes'])->name('workorders.materials');



    Route::get('buscar-material', [OrdenesTrabajoController::class, 'buscarMaterial'])->name('herramientas.search');

    Route::post('/registrar/herramienta/{workorder}',[OrdenesTrabajoController::class, 'asignarMaterial'])->name('workorders.materials.asignar');

    Route::post('/pedidos-materiales/{pedido}/asignar', [OrdenesTrabajoController::class, 'solicitarMaterial'])->name('pedidos.materiales.asignar');


});









Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return to_route('login');
})->middleware(['web','auth'])->name('logout');
