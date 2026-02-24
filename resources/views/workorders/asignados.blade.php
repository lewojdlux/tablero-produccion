@extends('layouts.app')

@section('content')
    <style>
        [v-cloak] {
            display: none;
        }

        .overflow-visible {
            overflow: visible !important;
        }

        .dropdown-menu {
            z-index: 9999 !important;
        }
    </style>
    <div id="app" class="space-y-4" v-cloak>

        {{-- Header --}}
        <div class="flex items-center justify-between">

            <h2 class="text-lg font-semibold">Orden de trabajo</h2>

            <div class="flex items-center gap-4">

                <!-- CAMPANA DE NOTIFICACIONES -->
                <div class="relative" @click="toggleNotificaciones">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor" class="w-6 h-6 cursor-pointer hover:text-indigo-600 transition">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.857 17.657A2 2 0 0113 19H11a2 2 0 01-1.857-1.343m5.714 0A6 6 0 006 11V8a6 6 0 1112 0v3a6 6 0 01-1.429 3.657m-5.714 0L6 11m0 0H3" />
                    </svg>

                    <!-- CONTADOR -->
                    <span v-if="notificaciones.length > 0"
                        class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px]
                         rounded-full px-1.5 py-0.5">
                        @{{ notificaciones.length }}
                    </span>

                    <!-- DROPDOWN DE NOTIFICACIONES -->
                    <div v-if="mostrarNotificaciones"
                        class="absolute right-0 mt-2 w-80 bg-white border border-zinc-200 shadow-lg rounded-lg z-50">

                        <div class="p-2 text-xs font-semibold bg-zinc-100 border-b">
                            Notificaciones
                        </div>

                        <div class="max-h-64 overflow-y-auto">

                            <div v-for="n in notificaciones" :key="n.id"
                                class="px-3 py-2 text-xs border-b hover:bg-zinc-50 cursor-pointer"
                                @click="abrirNotificacion(n)">

                                <strong>@{{ n.data.title }}</strong>
                                <p class="text-zinc-700">@{{ n.data.message }}</p>
                                <small class="text-zinc-500">@{{ n.created_at }}</small>
                            </div>

                            <a href="{{ route('notificaciones.index') }}"
                                class="block text-center text-xs py-2 hover:bg-zinc-100">
                                Ver todas las notificaciones
                            </a>

                            <div v-if="notificaciones.length === 0" class="px-3 py-6 text-center text-zinc-500 text-xs">
                                No hay notificaciones
                            </div>

                        </div>
                    </div>
                </div>


            </div>
        </div>



        {{-- Filtros --}}
        <details class="rounded border border-zinc-200 bg-zinc-50 p-2 pb-3 mb-3" open>
            <summary class="cursor-pointer text-[11px] text-zinc-700 select-none leading-none">Filtros</summary>
            <div class="mt-1.5 flex flex-wrap items-center gap-1 ">
                <input type="text" placeholder="Buscar por documento, cliente o asesor" v-model="search"
                    class="h-8 w-[26rem] max-w-full rounded border border-zinc-300 px-2 text-[11px]
                    focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />


                <select wire:model.live="perPage"
                    class="h-8 w-[5.5rem] rounded border border-zinc-300 px-2 text-[11px] bg-white
                           focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                </select>
            </div>
        </details>

        @php
            $perfil = (int) (auth()->user()->perfil_usuario_id ?? 0);
            $isAdmin = in_array($perfil, [1, 2], true);
            $isAdminInstalador = $perfil === 6;
            $isInstalador = $perfil === 7;
            $isAsesor = $perfil === 5;
        @endphp

        @if (session('success'))
            <div class="alert alert-success border border-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border border-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        {{-- Tabla --}}
        <div class="rounded-lg border border-zinc-200">
            <div class="overflow-x-auto overflow-visible">
                <table class="table-responsive">
                    <thead>
                        <th class="px-2 py-1 font-medium">Consecutivo</th>
                        <th class="px-2 py-1 font-medium">Instalador</th>
                        <th class="px-2 py-1 font-medium">Cliente</th>
                        <th class="px-2 py-1 font-medium">Asesor</th>
                        <th class="px-2 py-1 font-medium text-center">Estado</th>
                        <th class="px-2 py-1 font-medium text-center">Acciones</th>
                    </thead>

                    <tbody>
                        <tr v-for="workOrder in filteredWorkOrders" :key="workOrder.id_work_order"
                            class="border-b border-zinc-200 hover:bg-zinc-50">

                            <td class="px-2 py-1">@{{ workOrder.n_documento }}</td>

                            <td class="px-2 py-1">
                                @{{ workOrder.instalador ? workOrder.instalador.nombre_instalador : '' }}
                            </td>

                            <td class="px-2 py-1">@{{ workOrder.tercero }}</td>

                            <td class="px-2 py-1">@{{ workOrder.vendedor }}</td>

                            <td class="px-2 py-1 text-center">
                                <span v-if="workOrder.status === 'completed'" class="btn-sm btn  btn-success">
                                    Finalizada
                                </span>

                                <span v-else-if="workOrder.status === 'in_progress'"
                                    class="btn-sm btn  btn-warning text-dark">
                                    En Progreso
                                </span>

                                <span v-else-if="workOrder.status === 'pending'" class="btn-sm btn  btn-danger">
                                    Pendiente
                                </span>

                                <span v-else class="badge bg-secondary">
                                    @{{ workOrder.status }}
                                </span>
                            </td>


                            <td class="text-center align-middle">
                                <div class="dropdown" ata-bs-auto-close="outside">

                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-3" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-h me-1"></i>
                                        Acciones
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">

                                        {{-- ================= INICIAR OT ================= --}}
                                        @if ($isInstalador)
                                            <li v-if="workOrder.status === 'pending'">
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="iniciarOT(workOrder.id_work_order)">
                                                    <i class="fas fa-play me-2 text-danger"></i>
                                                    Iniciar OT
                                                </a>
                                            </li>
                                        @endif


                                        {{-- ================= FINALIZAR OT ================= --}}
                                        @if ($isInstalador)
                                            <li v-if="workOrder.status === 'in_progress'">
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="irFinalizarOT(workOrder.id_work_order)">
                                                    <i class="fas fa-check me-2 text-success"></i>
                                                    Finalizar OT
                                                </a>
                                            </li>
                                        @endif


                                        {{-- ================= ASIGNAR MATERIAL ================= --}}
                                        @if ($isInstalador)
                                            <li v-if="workOrder.status !== 'completed'">
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="irAsignarMaterial(workOrder.id_work_order)">
                                                    <i class="fas fa-tools me-2 text-warning"></i>
                                                    Asignar material
                                                </a>
                                            </li>
                                        @endif


                                        {{-- ================= ASIGNAR INSTALADORES ================= --}}
                                        @if ($isInstalador)
                                            <li v-if="workOrder.status !== 'completed'">
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="abrirModalAsignacion(workOrder.id_work_order)">
                                                    <i class="fas fa-user-cog me-2 text-primary"></i>
                                                    Asignar Instaladores
                                                </a>
                                            </li>
                                        @endif


                                        @if ($isInstalador)
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="abrirModalProgramacion(workOrder)">
                                                    <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                                    Programar OT
                                                </a>
                                            </li>
                                        @endif


                                        {{-- ================= VER PEDIDO (INSTALADOR + ADMIN) ================= --}}
                                        @if ($isInstalador || $isAdmin || $isAdminInstalador)
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="verPedidoMaterialHgi(workOrder.id_work_order)">
                                                    <i class="fas fa-file-alt me-2 text-warning"></i>
                                                    Ver Pedido
                                                </a>
                                            </li>
                                        @endif


                                        {{-- ================= VER OT FINALIZADA (SOLO ADMIN) ================= --}}
                                        @if ($isAdmin || $isAdminInstalador)
                                            <li v-if="workOrder.status === 'completed'">
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="verOTFinalizada(workOrder.id_work_order)">
                                                    <i class="fas fa-check-circle me-2 text-success"></i>
                                                    Ver OT Finalizada
                                                </a>
                                            </li>
                                        @endif


                                        {{-- ================= VER FINANCIERO (ADMIN) ================= --}}
                                        @if ($isAdmin || $isAdminInstalador)
                                            <li v-if="workOrder.status === 'completed'">
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="verManoObra(workOrder.id_work_order, workOrder.pd_servicio)">
                                                    <i class="fas fa-coins me-2 text-info"></i>
                                                    Ver Financiero
                                                </a>
                                            </li>
                                        @endif


                                        {{-- ================= VER SOLICITUDES MATERIAL (ADMIN) ================= --}}
                                        @if ($isAdmin || $isAdminInstalador)
                                            <li v-if="workOrder.pedidos_materiales_count > 0">
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="irCargarSolicitud(workOrder.id_work_order)">
                                                    <i class="fas fa-clipboard-list me-2 text-warning"></i>
                                                    Ver Solicitudes Material
                                                </a>
                                            </li>
                                        @endif

                                        @if ($isAsesor)
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent="verProgramacionOT(workOrder)">
                                                    <i class="fas fa-eye me-2 text-info"></i>
                                                    Ver Programación
                                                </a>
                                            </li>
                                        @endif

                                    </ul>
                                </div>
                            </td>


                        </tr>

                        <tr v-if="filteredWorkOrders.length === 0">
                            <td colspan="7" class="px-2 py-6 text-center text-zinc-500">
                                Sin resultados…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-2 text-xs">
            {{ $dataMatrial->links() }}
        </div>


        <!-- TOASTS -->
        <div class="fixed top-4 right-4 z-[9999] space-y-2">

            <div v-for="t in toasts" :key="t.id"
                class="w-80 bg-white shadow-lg rounded-lg animate-slide-in overflow-hidden border-l-4"
                :class="{
                    'border-green-500': t.type === 'success',
                    'border-red-500': t.type === 'danger',
                    'border-yellow-500': t.type === 'warning',
                    'border-blue-500': t.type === 'info'
                }">

                <div class="p-3">

                    <div class="flex justify-between items-start">

                        <strong class="text-sm"
                            :class="{
                                'text-green-600': t.type === 'success',
                                'text-red-600': t.type === 'danger',
                                'text-yellow-600': t.type === 'warning',
                                'text-blue-600': t.type === 'info'
                            }">
                            @{{ t.title }}
                        </strong>

                        <button class="text-zinc-400 hover:text-zinc-700" @click="removeToast(t.id)">
                            ✕
                        </button>

                    </div>

                    <p class="text-xs text-zinc-700 mt-1">
                        @{{ t.message }}
                    </p>

                    <small class="text-[10px] text-zinc-400">
                        @{{ t.time }}
                    </small>

                </div>
            </div>

        </div>


        <!-- MODAL PEDIDO HGI -->
        <div v-if="mostrarPedidoHgi" class="modal fade show d-block" tabindex="-1"
            style="background: rgba(0,0,0,0.6);">

            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">

                    <!-- ================= HEADER ================= -->
                    <div class="modal-header bg-dark text-white">
                        <div>
                            <h5 class="modal-title fw-semibold">
                                Pedido HGI
                            </h5>

                            <small>
                                📦 PD Global:
                                <strong>@{{ pdGlobalNumero }}</strong>

                                <span v-if="pdServicioNumero">
                                    | 🔧 PD Servicio:
                                    <strong>@{{ pdServicioNumero }}</strong>
                                </span>
                            </small>

                            <small>
                                Cliente:
                                <strong>@{{ pedidoHgi[0]?.cliente }}</strong>
                                |
                                Asesor:
                                <strong>
                                    @{{ workOrders.find(w => w.id_work_order === selectedWorkOrderId)?.vendedor }}
                                </strong>
                            </small>
                        </div>

                        <button type="button" class="btn-close btn-close-white" @click="cerrarPedidoHgi">
                        </button>
                    </div>

                    <!-- ================= BODY ================= -->
                    <div class="modal-body bg-light">

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">

                                <!-- ================= THEAD ================= -->
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Cant.</th>

                                        @if ($isAdmin || $isAdminInstalador)
                                            <th>Vlr Unit</th>
                                            <th>Subtotal</th>
                                            <th>Descuento</th>
                                            <th>Total</th>
                                        @endif
                                    </tr>
                                </thead>

                                <tbody>

                                    <!-- ================= PD GLOBAL ================= -->
                                    <tr class="table-primary">
                                        <td colspan="{{ $isAdmin || $isAdminInstalador ? 7 : 3 }}"
                                            class="fw-bold text-center">
                                            📦 Pedido Global
                                        </td>
                                    </tr>

                                    <tr v-for="(p, index) in pedidoGlobal" :key="'global-' + index">

                                        <td>@{{ p.codigo_producto }}</td>

                                        <td>@{{ p.producto }}</td>

                                        <td class="text-center">
                                            @{{ Number(p.cantidad).toFixed(2) }}
                                        </td>

                                        @if ($isAdmin || $isAdminInstalador)
                                            <td class="text-end">
                                                $ @{{ Number(p.valor_unitario).toLocaleString('es-CO') }}
                                            </td>

                                            <td class="text-end">
                                                $ @{{ Number(p.subtotal).toLocaleString('es-CO') }}
                                            </td>

                                            <td class="text-end text-danger">
                                                $ @{{ Number(p.valor_descuento).toLocaleString('es-CO') }}
                                            </td>

                                            <td class="text-end fw-bold text-success">
                                                $ @{{ Number(p.total_con_descuento).toLocaleString('es-CO') }}
                                            </td>
                                        @endif
                                    </tr>

                                    <!-- TOTAL PD GLOBAL -->
                                    @if ($isAdmin || $isAdminInstalador)
                                        <tr class="table-secondary">
                                            <td colspan="6" class="text-end fw-bold">
                                                TOTAL PD GLOBAL
                                            </td>
                                            <td class="text-end fw-bold">
                                                $ @{{ totalGlobal.toLocaleString('es-CO') }}
                                            </td>
                                        </tr>
                                    @endif


                                    <!-- ================= PD SERVICIO ================= -->
                                    <tr v-if="pedidoServicio.length" class="table-warning">

                                        <td colspan="{{ $isAdmin || $isAdminInstalador ? 7 : 3 }}"
                                            class="fw-bold text-center">
                                            🔧 Pedido Servicio (Instalación)
                                        </td>
                                    </tr>

                                    <tr v-for="(p, index) in pedidoServicio" :key="'servicio-' + index">

                                        <td>@{{ p.codigo_producto }}</td>

                                        <td>@{{ p.producto }}</td>

                                        <td class="text-center">
                                            @{{ Number(p.cantidad).toFixed(2) }}
                                        </td>

                                        @if ($isAdmin || $isAdminInstalador)
                                            <td class="text-end">
                                                $ @{{ Number(p.valor_unitario).toLocaleString('es-CO') }}
                                            </td>

                                            <td class="text-end">
                                                $ @{{ Number(p.subtotal).toLocaleString('es-CO') }}
                                            </td>

                                            <td class="text-end text-danger">
                                                $ @{{ Number(p.valor_descuento).toLocaleString('es-CO') }}
                                            </td>

                                            <td class="text-end fw-bold text-success">
                                                $ @{{ Number(p.total_con_descuento).toLocaleString('es-CO') }}
                                            </td>
                                        @endif
                                    </tr>

                                    <!-- TOTAL PD SERVICIO -->
                                    @if ($isAdmin || $isAdminInstalador)
                                        <tr v-if="pedidoServicio.length" class="table-secondary">
                                            <td colspan="6" class="text-end fw-bold">
                                                TOTAL PD SERVICIO
                                            </td>
                                            <td class="text-end fw-bold">
                                                $ @{{ totalServicio.toLocaleString('es-CO') }}
                                            </td>
                                        </tr>

                                        <!-- TOTAL GENERAL -->
                                        <tr class="table-dark">
                                            <td colspan="6" class="text-end fw-bold text-uppercase">
                                                TOTAL GENERAL
                                            </td>
                                            <td class="text-end fw-bold">
                                                $ @{{ totalGeneral.toLocaleString('es-CO') }}
                                            </td>
                                        </tr>
                                    @endif

                                </tbody>
                            </table>
                        </div>

                    </div>

                    <!-- ================= FOOTER ================= -->
                    <div class="modal-footer bg-white">
                        <button class="btn btn-secondary" @click="cerrarPedidoHgi">
                            Cerrar
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <!-- FIN MODAL PEDIDO HGI -->

        <!-- MODAL MANO DE OBRA -->
        <div v-if="mostrarManoObra" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6);">

            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">

                    <!-- HEADER -->
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title fw-semibold">
                            <i class="fas fa-coins me-2"></i>
                            Resumen Financiero OT
                        </h5>
                        <button type="button" class="btn-close btn-close-white"
                            @click="mostrarManoObra = false"></button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body bg-light">

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">

                                <thead class="text-uppercase small text-muted border-bottom">
                                    <tr>
                                        <th>Instalador</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Horas</th>
                                        <th class="text-end">Valor Hora</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <tr v-for="m in manoObra" :key="m.id_instalador + m.tipo">
                                        <td>@{{ m.nombre_instalador }}</td>
                                        <td>@{{ m.tipo }}</td>
                                        <td class="text-center">@{{ Number(m.horas).toFixed(2) }}</td>
                                        <td class="text-end">$ @{{ Number(m.valor_hora).toLocaleString('es-CO') }}</td>
                                        <td class="text-end fw-bold">$ @{{ Number(m.total).toLocaleString('es-CO') }}</td>

                                    </tr>

                                </tbody>

                                <tfoot class="border-top">

                                    <tr>
                                        <td colspan="4" class="text-end fw-semibold text-muted">
                                            TOTAL MANO DE OBRA
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            $ @{{ Number(manoObraTotal).toLocaleString('es-CO') }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="4" class="text-end fw-semibold text-muted">
                                            TOTAL MATERIAL
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            $ @{{ Number(materialTotal).toLocaleString('es-CO') }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="4" class="text-end fw-semibold text-muted">
                                            TOTAL PEDIDO
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            $ @{{ Number(pedidoTotal).toLocaleString('es-CO') }}
                                        </td>
                                    </tr>

                                    <tr class="border-top">
                                        <td colspan="4" class="text-end fw-bold text-uppercase">
                                            UTILIDAD
                                        </td>
                                        <td class="text-end fw-bold"
                                            :class="utilidad >= 0 ? 'text-success' : 'text-danger'">
                                            $ @{{ Number(utilidad).toLocaleString('es-CO') }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="4" class="text-end fw-semibold text-muted">
                                            MARGEN %
                                        </td>
                                        <td class="text-end fw-bold"
                                            :class="porcentajeUtilidad >= 0 ? 'text-success' : 'text-danger'">
                                            @{{ porcentajeUtilidad }} %
                                        </td>
                                    </tr>

                                </tfoot>

                            </table>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer bg-white border-0">

                        <!-- Botón Exportar (solo admin) -->
                        @if ($isAdmin || $isAdminInstalador)
                            <button class="btn btn-outline-success" @click="exportarExcel(selectedWorkOrderId)">
                                <i class="fas fa-file-excel me-2"></i>
                                Exportar Excel
                            </button>
                        @endif


                        <button class="btn btn-outline-dark" @click="mostrarManoObra = false">
                            Cerrar
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <!-- FIN MODAL MANO DE OBRA -->



        <!-- Modal para asignar instaladores a la OT -->
        <div v-if="mostrarAsignacion" class="modal fade show d-block" tabindex="-1"
            style="background: rgba(0,0,0,0.6);">

            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">

                    <!-- HEADER -->
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">
                            Asignar Instaladores
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="cerrarAsignacion">
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Instalador Principal
                            </label>

                            <select class="form-select" v-model="formAsignacion.instalador_principal">

                                <option value="">Seleccione...</option>

                                <option v-for="i in instaladores" :value="i.id_instalador">
                                    @{{ i.nombre_instalador }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">
                                Acompañantes
                            </label>

                            <select class="form-select" multiple v-model="formAsignacion.acompanantes">

                                <option v-for="i in instaladoresDisponibles" :value="i.id_instalador">
                                    @{{ i.nombre_instalador }}
                                </option>

                            </select>

                            <small class="text-muted">
                                Puede seleccionar varios.
                            </small>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="cerrarAsignacion">
                            Cancelar
                        </button>

                        <button class="btn btn-dark" @click="guardarAsignacion">
                            Guardar Asignación
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <!-- FIN Modal para asignar instaladores a la OT -->

        <!-- Modal para programar la OT -->
        <div v-if="mostrarProgramacion" class="modal fade show d-block" tabindex="-1"
            style="background: rgba(0,0,0,0.6);">

            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">

                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">
                            @if ($isInstalador)
                                Programar OT
                            @else
                                Programación OT
                            @endif
                        </h5>

                        <button type="button" class="btn-close btn-close-white" @click="mostrarProgramacion = false">
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Fecha Programada</label>

                            <input type="date" v-model="formProgramacion.fecha_programada" class="form-control"
                                :readonly="{{ $isAsesor ? 'true' : 'false' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha Estimada Finalización</label>

                            <input type="date" v-model="formProgramacion.fecha_programada_fin" class="form-control"
                                :readonly="{{ $isAsesor ? 'true' : 'false' }}">
                        </div>

                        <div>
                            <label class="form-label">Observaciones</label>

                            <textarea v-model="formProgramacion.observacion_programacion" class="form-control" rows="3"
                                :readonly="{{ $isAsesor ? 'true' : 'false' }}">
                    </textarea>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button class="btn btn-secondary" @click="mostrarProgramacion = false">
                            Cerrar
                        </button>

                        @if ($isInstalador)
                            <button class="btn btn-dark" @click="guardarProgramacion">
                                Guardar
                            </button>
                        @endif

                    </div>

                </div>
            </div>
        </div>
        <!-- FIN Modal para programar la OT -->





    </div>
@endsection



@push('scripts')
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>




    <script>
        const routePedidoMaterial = "{{ route('pedidos.materiales.byOrden', ':id') }}";
        const routeVerOT = "{{ route('workorders.show', ':id') }}";
        const routeAsignarMaterial = "{{ route('workorders.materials', ':id') }}";
        const routePedidoShow = "{{ route('pedidos.materiales.show', ':id') }}";
        const routeSolicitudCreate = "{{ route('solicitudes.create', ':id') }}";
        const routeSolicitudShow = "{{ route('solicitudes.aprobados', ':id') }}";
        const routeFinalizarOT = "{{ route('workorders.finalizar.form', ':id') }}";
        const routeVerOTFinalizada = "{{ route('workorders.finalizadas.show', ':id') }}";
        const routePedidoHgiAdmin = "{{ route('workorders.hgi.pedido.admin', ':id') }}";
        const routePedidoHgiInstalador = "{{ route('workorders.hgi.pedido', ':id') }}";

        window.AUTH_USER_ID = {{ auth()->id() }};
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const {
                createApp
            } = Vue;

            createApp({
                data() {
                    return {
                        search: "",
                        workOrders: @json($dataMatrial->items()), // viene del controlador,
                        notificaciones: @json($notificaciones ?? []),
                        mostrarNotificaciones: false,
                        toasts: [],
                        selectedWorkOrderId: null,
                        pedidoHgi: [],
                        mostrarPedidoHgi: false,
                        manoObra: [],
                        mostrarManoObra: false,
                        pedidoTotal: 0,
                        manoObraTotal: 0,
                        utilidad: 0,
                        materialTotal: 0,
                        mostrarAsignacion: false,
                        instaladores: [],
                        formAsignacion: {
                            work_order_id: null,
                            instalador_principal: "",
                            acompanantes: []
                        },
                        porcentajeUtilidad: 0,

                        mostrarProgramacion: false,
                        formProgramacion: {
                            id_work_order: null,
                            fecha_programada: "",
                            fecha_programada_fin: "",
                            observacion_programacion: ""
                        },

                    }
                },

                mounted() {



                    // Escuchar solicitudes de material EN TIEMPO REAL
                    const esperarEcho = setInterval(() => {
                        if (window.Echo) {
                            clearInterval(esperarEcho);

                            window.Echo
                                .private(`App.Models.User.${window.AUTH_USER_ID}`)
                                .notification((payload) => {

                                    this.pushToast(payload);

                                    this.notificaciones.unshift({
                                        id: Date.now(),
                                        data: payload,
                                        created_at: new Date().toLocaleString()
                                    });


                                    // 👉 Notificación del navegador SOLO si no está activa la pestaña
                                    if (document.visibilityState !== 'visible') {
                                        this.showBrowserNotification(payload);
                                    }


                                });

                        }
                    }, 300);



                },

                computed: {

                    instaladoresDisponibles() {
                        return this.instaladores.filter(i =>
                            i.id_instalador != this.formAsignacion.instalador_principal
                        );
                    },

                    otSeleccionada() {
                        return this.workOrders.find(
                            w => w.id_work_order === this.selectedWorkOrderId
                        );
                    },

                    pdGlobalNumero() {
                        return this.otSeleccionada?.pedido ?? '';
                    },

                    pdServicioNumero() {
                        return this.otSeleccionada?.pd_servicio ?? '';
                    },

                    pedidoGlobal() {
                        const ot = this.workOrders.find(w => w.id_work_order === this.selectedWorkOrderId);
                        if (!ot) return [];
                        return this.pedidoHgiAgrupado.filter(p => p.pedido == ot.pedido);
                    },

                    pedidoServicio() {
                        const ot = this.workOrders.find(w => w.id_work_order === this.selectedWorkOrderId);
                        if (!ot) return [];
                        return this.pedidoHgiAgrupado.filter(p => p.pedido != ot.pedido);
                    },

                    totalGlobal() {
                        return this.pedidoGlobal.reduce((acc, p) =>
                            acc + Number(p.total_con_descuento), 0);
                    },

                    totalServicio() {
                        return this.pedidoServicio.reduce((acc, p) =>
                            acc + Number(p.total_con_descuento), 0);
                    },

                    totalGeneral() {
                        return this.totalGlobal + this.totalServicio;
                    },


                    filteredWorkOrders() {
                        const s = this.search.toLowerCase();
                        return this.workOrders.filter(w =>
                            w.n_documento.toString().includes(s) ||
                            w.tercero.toLowerCase().includes(s) ||
                            w.vendedor.toLowerCase().includes(s)
                        );
                    },

                    pedidoHgiAgrupado() {

                        const mapa = {};

                        this.pedidoHgi.forEach(p => {

                            const key = `${p.pedido}_${p.codigo_producto}`;

                            if (!mapa[key]) {
                                mapa[key] = {
                                    ...p,
                                    cantidad: parseFloat(p.cantidad) || 0,
                                    subtotal: parseFloat(p.subtotal) || 0,
                                    valor_descuento: parseFloat(p.valor_descuento) || 0,
                                    total_con_descuento: parseFloat(p.total_con_descuento) || 0
                                };
                            }

                        });

                        return Object.values(mapa);
                    }


                },

                methods: {


                    limpiarNumero(valor) {

                        if (!valor) return 0;

                        // Convertir a string
                        let limpio = String(valor)
                            .replace(/\./g, '') // quitar puntos de miles
                            .replace(',', '.'); // cambiar coma decimal por punto

                        return parseFloat(limpio) || 0;
                    },


                    // Mostrar/ocultar fila según búsqueda
                    shouldHide(doc, vendedor, cliente) {
                        const s = this.search.toLowerCase();
                        if (!s) return "";
                        const t = `${doc} ${vendedor} ${cliente}`.toLowerCase();
                        return t.includes(s) ? "" : "display:none;";
                    },



                    abrirModalProgramacion(workOrder) {

                        this.formProgramacion = {
                            id_work_order: workOrder.id_work_order,
                            fecha_programada: workOrder.fecha_programada ?? "",
                            fecha_programada_fin: workOrder.fecha_programada_fin ?? "",
                            observacion_programacion: workOrder.observacion_programacion ?? ""
                        };

                        this.mostrarProgramacion = true;
                    },

                    async guardarProgramacion() {

                        if (!this.formProgramacion.fecha_programada) {
                            alert("Debe seleccionar fecha programada.");
                            return;
                        }

                        const inicio = this.formProgramacion.fecha_programada;
                        const fin = this.formProgramacion.fecha_programada_fin;

                        if (!inicio) {
                            this.pushToast('warning', 'Validación',
                                'Debe seleccionar fecha programada.');
                            return;
                        }

                        if (fin && fin < inicio) {
                            this.pushToast(
                                'danger',
                                'Validación',
                                'La fecha final no puede ser menor a la fecha inicial.'
                            );
                            return;
                        }

                        const resp = await fetch('/ordenes-trabajo/programar', {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(this.formProgramacion)
                        });

                        const json = await resp.json();

                        if (!resp.ok || !json.success) {
                            alert(json.message ?? "Error guardando programación.");
                            return;
                        }

                        // ACTUALIZAR EN MEMORIA SIN RECARGAR
                        const index = this.workOrders.findIndex(
                            w => w.id_work_order === this.formProgramacion.id_work_order
                        );

                        if (index !== -1) {
                            this.workOrders[index].fecha_programada =
                                this.formProgramacion.fecha_programada;

                            this.workOrders[index].fecha_programada_fin =
                                this.formProgramacion.fecha_programada_fin;

                            this.workOrders[index].observacion_programacion =
                                this.formProgramacion.observacion_programacion;
                        }

                        this.mostrarProgramacion = false;

                        this.pushToast(
                            'success',
                            'Programación actualizada',
                            'La programación fue guardada correctamente.'
                        );
                    },


                    async verProgramacionOT(workOrder) {

                        try {

                            const resp = await fetch(
                                `/ordenes-trabajo/${workOrder.id_work_order}/programacion`);

                            if (!resp.ok) {
                                this.pushToast('danger', 'Error',
                                'No se pudo obtener la programación.');
                                return;
                            }

                            const data = await resp.json();

                            this.formProgramacion = {
                                id_work_order: data.id_work_order,
                                fecha_programada: data.fecha_programada ?? "",
                                fecha_programada_fin: data.fecha_programada_fin ?? "",
                                observacion_programacion: data.observacion_programacion ?? ""
                            };

                            this.mostrarProgramacion = true;

                        } catch (error) {

                            this.pushToast('danger', 'Error', 'Error consultando programación.');
                            console.error(error);

                        }
                    },

                    async iniciarOT(id) {

                        if (!confirm("¿Desea iniciar esta orden de trabajo?")) return;

                        const url = "{{ route('workorders.start', ':id') }}".replace(':id', id);

                        const resp = await fetch(url, {
                            method: "POST", // YA NO PUT
                            credentials: "same-origin",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            // YA NO USES _method
                            body: JSON.stringify({})
                        });

                        const json = await resp.json();

                        if (!json.success) {
                            alert(json.message);
                            return;
                        }

                        const index = this.workOrders.findIndex(w => w.id_work_order === id);
                        if (index !== -1) {
                            this.workOrders[index].status = "in_progress";
                        }

                        alert("Orden iniciada correctamente.");
                    },

                    irAsignarMaterial(id) {
                        window.location.href = routeAsignarMaterial.replace(':id', id);
                    },

                    toggleNotificaciones() {
                        this.mostrarNotificaciones = !this.mostrarNotificaciones;
                    },

                    abrirNotificacion(n) {

                        fetch(`/notificaciones/${n.id}/leer`, {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            }
                        });


                        // Redirigir al pedido
                        if (n.data && n.data.pedido_id) {
                            window.location.href = routePedidoShow.replace(':id', n.data.pedido_id);
                        }
                    },

                    pushToast(type, title, message) {

                        const id = Date.now();

                        const colors = {
                            success: "border-success",
                            danger: "border-danger",
                            warning: "border-warning",
                            info: "border-primary"
                        };

                        this.toasts.unshift({
                            id,
                            type,
                            title,
                            message,
                            time: new Date().toLocaleTimeString()
                        });

                        setTimeout(() => {
                            this.removeToast(id);
                        }, 5000);
                    },


                    removeToast(id) {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    },


                    verPedidoMaterial(id) {
                        window.location.href = routePedidoMaterial.replace(':id', id);
                    },

                    verOT(id) {
                        window.location.href = routeVerOT.replace(':id', id);
                    },

                    isTabInactive() {
                        return document.visibilityState !== 'visible';
                    },

                    showBrowserNotification(payload) {
                        if (!('Notification' in window)) return;
                        if (Notification.permission !== 'granted') return;

                        const notification = new Notification(payload.title, {
                            body: payload.message,
                            icon: '/favicon.ico', // o el icono que quieras
                            tag: `pedido-${payload.pedido_id}`, // evita duplicados
                        });

                        notification.onclick = () => {
                            window.focus();
                            window.location.href = `/pedidos-materiales/${payload.pedido_id}`;
                        };
                    },

                    irCargarSolicitud(id) {
                        if (!id) {
                            alert('No existe pedido de material asociado');
                            return;
                        }

                        window.location.href =
                            routeSolicitudCreate.replace(':id', id);
                    },

                    verSolicitud(id) {
                        if (!id) {
                            alert('No existe pedido de material asociado');
                            return;
                        }

                        window.location.href =
                            routeSolicitudShow.replace(':id', id);
                    },

                    irFinalizarOT(id) {
                        window.location.href = routeFinalizarOT.replace(':id', id);
                    },

                    verOTFinalizada(id) {
                        window.location.href = routeVerOTFinalizada.replace(':id', id);
                    },

                    verPedidoMaterialHgi(workOrderId) {
                        this.selectedWorkOrderId = workOrderId;
                        this.cargarPedidoHgi(workOrderId);
                    },

                    async cargarPedidoHgi(workOrderId) {
                        try {
                            let url;

                            @if ($isAdmin || $isAdminInstalador)
                                url = routePedidoHgiAdmin.replace(':id', workOrderId);
                            @else
                                url = routePedidoHgiInstalador.replace(':id', workOrderId);
                            @endif

                            const resp = await fetch(url, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });

                            if (!resp.ok) {
                                const error = await resp.json();
                                alert(error.message || "No autorizado");
                                return;
                            }

                            this.pedidoHgi = await resp.json();
                            this.mostrarPedidoHgi = true;

                        } catch (e) {
                            console.error(e);
                            this.pedidoHgi = [];
                            this.mostrarPedidoHgi = false;
                        }
                    },

                    cerrarPedidoHgi() {
                        this.mostrarPedidoHgi = false;
                        this.pedidoHgi = [];
                    },

                    async verManoObra(id, pedido) {

                        this.selectedWorkOrderId = id;

                        const resp = await fetch(`/ordenes-trabajo/${id}/mano-obra?pedido=${pedido}`);

                        if (!resp.ok) {
                            alert(data.error || "Ocurrió un error procesando la información.");
                            return;
                        }

                        const data = await resp.json();

                        this.manoObra = data.mano_obra;
                        this.manoObraTotal = data.mano_obra_total;
                        this.materialTotal = data.solicitud_total;
                        this.pedidoTotal = data.pedido_total;
                        this.utilidad = data.utilidad;
                        this.porcentajeUtilidad = data.porcentaje_utilidad;
                        this.mostrarManoObra = true;


                    },


                    async exportarExcel(id) {

                        if (!id) {
                            this.pushToast(
                                'warning',
                                'Validación',
                                'No se encontró la orden.'
                            );
                            return;
                        }

                        try {

                            const resp = await fetch(
                                `/ordenes-trabajo/${id}/exportar-financiero-excel`, {
                                    method: 'GET',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                }
                            );

                            // Si el backend devuelve error JSON
                            if (!resp.ok) {
                                const data = await resp.json();
                                this.pushToast(
                                    'danger',
                                    'Error al exportar',
                                    data.message || 'No se pudo generar el archivo.'
                                );
                                return;
                            }

                            // Si es correcto → descargar archivo
                            const blob = await resp.blob();
                            const url = window.URL.createObjectURL(blob);

                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `OT_${id}_Resumen_Financiero.xlsx`;
                            document.body.appendChild(a);
                            a.click();
                            a.remove();

                            window.URL.revokeObjectURL(url);

                            this.pushToast(
                                'success',
                                'Exportación exitosa',
                                'El archivo fue generado correctamente.'
                            );

                        } catch (error) {

                            this.pushToast(
                                'danger',
                                'Error inesperado',
                                'Ocurrió un problema generando el Excel.'
                            );

                            console.error(error);
                        }
                    },


                    async abrirModalAsignacion(id) {
                        this.formAsignacion.work_order_id = id;

                        // cargar instaladores si no están
                        if (!this.instaladores.length) {
                            await this.cargarInstaladores();
                        }

                        // cargar selección actual
                        const resp = await fetch(`/ordenes-trabajo/${id}/instaladores`);
                        const data = await resp.json();

                        this.formAsignacion.instalador_principal = data.principal ?? "";
                        this.formAsignacion.acompanantes = data.acompanantes ?? [];

                        this.mostrarAsignacion = true;
                    },

                    cerrarAsignacion() {
                        this.mostrarAsignacion = false;
                        this.formAsignacion = {
                            work_order_id: null,
                            instalador_principal: "",
                            acompanantes: []
                        };
                    },

                    async cargarInstaladores() {
                        const resp = await fetch('/instaladores/listado');
                        this.instaladores = await resp.json();
                    },

                    async guardarAsignacion() {

                        if (!this.formAsignacion.instalador_principal) {
                            this.pushToast(
                                'warning',
                                'Validación',
                                'Debe seleccionar un instalador principal.'
                            );
                            return;
                        }

                        try {

                            const resp = await fetch('/ordenes-trabajo/asignar-instaladores', {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                },
                                body: JSON.stringify(this.formAsignacion)
                            });

                            const json = await resp.json();

                            if (!resp.ok || !json.success) {

                                this.pushToast(
                                    'danger',
                                    'Error',
                                    json.message ?? 'No se pudo guardar la asignación.'
                                );

                                return;
                            }

                            this.pushToast(
                                'success',
                                'Correcto',
                                'Instaladores asignados correctamente.'
                            );

                            this.cerrarAsignacion();

                            setTimeout(() => location.reload(), 1200);

                        } catch (error) {

                            this.pushToast(
                                'danger',
                                'Error inesperado',
                                'Ocurrió un problema al guardar.'
                            );

                            console.error(error);
                        }
                    }


                }
            }).mount("#app");

        });
    </script>
@endpush
