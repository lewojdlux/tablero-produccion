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
Route::middleware(['auth', 'perfil:1,2,5'])
    ->group(function () {


          // Dashboard admin
        Route::get('/',  \App\Livewire\Order\PendingList::class)
            ->name('dashboard');


        // Gestión de usuarios (admin/super)
        Route::get('/users', \App\Livewire\User\Index::class)->name('users.index');

        // Settings (perfil admin/super; muévelas si deben ser globales)
        Route::get('/settings/profile', \App\Livewire\User\Profile::class)->name('settings.profile');

        Route::get('/settings/password', \App\Livewire\User\Password::class)->name('settings.password');


        // Órdenes pendientes (admin/super)
        Route::get('/orders/pending', \App\Livewire\Order\PendingList::class)->name('orders.pending.list');




        // Rutas para órdenes de trabajo y pedidos de materiales
        Route::get('/ordenes-trabajo/asignar', [OrdenesTrabajoController::class, 'index'])->name('ordenes.trabajo.asignar');
        Route::post('/ordenes-trabajo/asignar', [OrdenesTrabajoController::class, 'store'])->name('ordenes.trabajo.store');
        Route::get('/pedidos-materiales/{pedido}', [OrdenesTrabajoController::class, 'show'])->name('pedidos.materiales.show');
        Route::get('/ordenes-trabajo/crear', [OrdenesTrabajoController::class, 'create'])->name('workorders.create');
        Route::get('/ordenes-trabajo/asignados', [OrdenesTrabajoController::class, 'indexAsignados'])->name('ordenes.trabajo.asignadas');
        Route::get('/pedidos-materiales/orden-trabajo/{orderId}', [OrdenesTrabajoController::class, 'verPedidoMaterial'])->name('pedidos.materiales.byOrden');
        Route::get('/ordenes-trabajo/{workorder}', [OrdenesTrabajoController::class, 'verOrden'])->name('workorders.show');

        // Rutas para notificaciones
        Route::get('/notificaciones', function () {
            $notificaciones = auth()->user()
                ->notifications()
                ->latest()
                ->paginate(20);

            return view('notificaciones.index', compact('notificaciones'));
        })->name('notificaciones.index');


        // Ruta para marcar notificación como leída y redirigir
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

        // Rutas para solicitudes de materiales
        Route::get('/ordenes-trabajo/solicitudes', [SolicitudesController::class, 'solicitudes'])->name('solicitudes.index');
        Route::get('/ordenes-trabajo/solicitudes/crear/{id}', [SolicitudesController::class, 'create'])->name('solicitudes.create');
        Route::post('/ordenes-trabajo/solicitudes/{id}/registrar', [SolicitudesController::class, 'importExcel'])->name('solicitudes.store');
        Route::get('/ordenes-trabajo/solicitudes/{pedidoMaterial}',[SolicitudesController::class, 'showSolicitud'])->name('solicitudes.show');
        Route::get('/ordenes-trabajo/solicitudes/{pedidoMaterial}/aprobados',[SolicitudesController::class, 'showSolicitud'])->name('solicitudes.aprobados');


        // Ver orden de trabajo finalizada
        Route::get('/ordenes-trabajo/instalador/finalizada/{id}', [OrdenesTrabajoController::class, 'verOrdenFinalizada'])->name('workorders.finalizadas.show');



});

// ===== Rutas CRM (perfiles 1,2,5) =====
Route::middleware(['auth', 'perfil:1,2,5'])
        ->prefix('crm')
        ->group(function () {

            // Vista principal CRM
            Route::get('/seguimiento', [SeguimientoCrmController::class, 'index'])
                ->name('portal-crm.seguimiento.index');

            // Data AJAX CRM
            Route::get('/seguimiento/data', [SeguimientoCrmController::class, 'data'])
                ->name('portal-crm.seguimiento.data');


            // Vista Eventos / Visitas CRM
            Route::get('eventos', [SeguimientoCrmController::class, 'eventosIndex'])
                ->name('portal-crm.eventos.index');

            // Data AJAX Eventos / Visitas CRM
            Route::get('eventos/data', [SeguimientoCrmController::class, 'eventosData'])
                ->name('portal-crm.eventos.data');

            // Listar Asesores con Eventos
            Route::get('/crm/asesores-eventos', [SeguimientoCrmController::class, 'asesoresEventos'])
            ->name('portal-crm.eventos.asesores');


             // Listar Asesores con Oportunidades
            Route::get('/crm/asesores-oportunidades', [SeguimientoCrmController::class, 'asesores'])
            ->name('portal-crm.oportunidades.asesores');

            // Listar Estados de Oportunidades por Asesor
            Route::get('/crm/asesores-estados', [SeguimientoCrmController::class, 'estadoOportunidad'])
            ->name('portal-crm.estados.asesores');


            // Exportar Eventos / Visitas CRM
            Route::get('/portal-crm/eventos/export',[SeguimientoCrmController::class, 'exportEventos'])->name('portal-crm.eventos.export');
            // Exportar Oportunidades CRM
            Route::get('/portal-crm/oportunidades/export',[SeguimientoCrmController::class, 'exportOportunidades'])->name('portal-crm.oportunidades.export');

            // Vista detalle Fotos Evento / Visita
            Route::get('eventos/{evento}/fotos', [SeguimientoCrmController::class, 'fotos'])->name('portal-crm.eventos.fotos');

            // Ruta para servir imágenes CRM
            Route::get('imagen', [SeguimientoCrmController::class, 'verImagen'])->name('crm.imagen');


            Route::post('/ordenes-trabajo/solicitudes/{pedidoMaterial}/approve',[SolicitudesController::class, 'approve'])->name('solicitudes.approve');



           // Vista KPIs
            Route::get('/kpis', [SeguimientoCrmController::class, 'kpisView'])
                ->name('portal-crm.seguimiento.kpis.view');

            Route::get('/kpis/data', [SeguimientoCrmController::class, 'kpis'])
                ->name('portal-crm.seguimiento.kpis.data');
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


    // Dashboard instalador
    Route::get('/instalador/ordenes-trabajo', [OrdenesTrabajoController::class, 'indexAsignados'])->name('ordenes.trabajo.asignados');
    Route::post('/ordenes-trabajo/{workorder}/start', [OrdenesTrabajoController::class, 'start'])->name('workorders.start');
    Route::get('/ordenes-trabajo/{id}/materials',[OrdenesTrabajoController::class, 'indexMaterialesOrdenes'])->name('workorders.materials');


    // Rutas para solicitudes de materiales
    Route::get('buscar-material', [OrdenesTrabajoController::class, 'buscarMaterial'])->name('herramientas.search');
    Route::post('/registrar/herramienta/{workorder}',[OrdenesTrabajoController::class, 'asignarMaterial'])->name('workorders.materials.asignar');
    Route::post('/pedidos-materiales/{pedido}/asignar', [OrdenesTrabajoController::class, 'solicitarMaterial'])->name('pedidos.materiales.asignar');

    // Finalizar orden de trabajo
    Route::get('/ordenes-trabajo/{id}/finalizar', [OrdenesTrabajoController::class, 'finalizarForm'])->name('workorders.finalizar.form');
    Route::post('/ordenes-trabajo/{id}/finalizar', [OrdenesTrabajoController::class, 'finalizar'])->name('workorders.finalizar');

    // Ver orden de trabajo finalizada
    Route::get('/ordenes-trabajo/finalizadas/{id}', [OrdenesTrabajoController::class, 'verOrdenFinalizada'])->name('workorders.finalizadas.show');

    // Ver pedido HGI de una orden de trabajo
    Route::get('/ordenes-trabajo/{id}/pedido-hgi',[OrdenesTrabajoController::class, 'verPedidoMaterialHgi'])->name('workorders.hgi.pedido');


});
