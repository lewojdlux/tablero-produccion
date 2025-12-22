@extends('layouts.app')

@section('content')
    <style>
        [v-cloak] {
            display: none;
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

                <a href="{{ route('workorders.create') }}"
                    class="inline-flex items-center gap-2 bg-black text-dark text-xs font-medium
            px-3 py-2 rounded-md shadow-sm hover:bg-zinc-800 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Agregar
                </a>

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
            $isInstalador = $perfil === 7;
            $isAsesor = $perfil === 5;
        @endphp

        {{-- Tabla --}}
        <div class="overflow-x-auto rounded-lg border border-zinc-200">
            <table class="w-full text-xs leading-tight">
                <thead>
                    <th class="px-2 py-1 font-medium">Consecutivo</th>
                    <th class="px-2 py-1 font-medium">Instalador</th>
                    <th class="px-2 py-1 font-medium">Cliente</th>
                    <th class="px-2 py-1 font-medium">Asesor</th>
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
                            <div class="d-flex justify-content-center flex-wrap gap-2">

                                @if ($isAdmin)
                                    <!-- VER OT -->
                                    <button class="btn btn-outline-secondary btn-sm" title="Ver orden de trabajo"
                                        @click="verOT(workOrder.id_work_order)">
                                        <i class="fas fa-eye me-1"></i>
                                        OT
                                    </button>

                                    <!-- VER SOLICITUD DE MATERIAL -->
                                    <button v-if="workOrder.pedidos_materiales_count > 0" class="btn btn-primary btn-sm"
                                        title="Ver solicitud de material"
                                        @click="verPedidoMaterial(workOrder.id_work_order)">
                                        <i class="fas fa-box-open me-1"></i>
                                        Solicitud
                                    </button>
                                @elseif ($isInstalador)
                                    <!-- ASIGNAR MATERIAL -->
                                    <button v-if="workOrder.status === 'in_progress'"
                                        class="btn btn-warning btn-sm text-dark" title="Asignar material"
                                        @click="irAsignarMaterial(workOrder.id_work_order)">
                                        <i class="fas fa-tools me-1"></i>
                                        Material
                                    </button>

                                    <!-- INICIAR OT -->
                                    <button v-else-if="workOrder.status === 'pending'" class="btn btn-danger btn-sm"
                                        title="Iniciar orden de trabajo" @click="iniciarOT(workOrder.id_work_order)">
                                        <i class="fas fa-play me-1"></i>
                                        Iniciar
                                    </button>
                                @endif

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

        <div class="pt-2 text-xs">
            {{ $dataMatrial->links() }}
        </div>


        <!-- TOASTS -->
        <div class="fixed top-4 right-4 z-[9999] space-y-2">
            <div v-for="t in toasts" :key="t.id"
                class="w-80 bg-white border border-zinc-200 shadow-lg rounded-lg
                        animate-slide-in overflow-hidden">

                <div class="p-3">
                    <div class="flex justify-between items-start">
                        <strong class="text-sm">@{{ t.title }}</strong>
                        <button class="text-zinc-400 hover:text-zinc-700"
                                @click="removeToast(t.id)">✕</button>
                    </div>

                    <p class="text-xs text-zinc-700 mt-1">@{{ t.message }}</p>

                    <small class="text-[10px] text-zinc-400">
                        @{{ t.time }}
                    </small>
                </div>
            </div>
        </div>





    </div>
@endsection



@push('scripts')
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>



    <script>
        const routePedidoMaterial = "{{ route('pedidos.materiales.byOrden', ':id') }}";
        const routeVerOT = "{{ route('workorders.show', ':id') }}";
        const routeAsignarMaterial = "{{ route('workorders.materials', ':id') }}";
        const routePedidoShow = "{{ route('pedidos.materiales.show', ':id') }}";


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
                    filteredWorkOrders() {
                        const s = this.search.toLowerCase();
                        return this.workOrders.filter(w =>
                            w.n_documento.toString().includes(s) ||
                            w.tercero.toLowerCase().includes(s) ||
                            w.vendedor.toLowerCase().includes(s)
                        );
                    }
                },

                methods: {


                    // Mostrar/ocultar fila según búsqueda
                    shouldHide(doc, vendedor, cliente) {
                        const s = this.search.toLowerCase();
                        if (!s) return "";
                        const t = `${doc} ${vendedor} ${cliente}`.toLowerCase();
                        return t.includes(s) ? "" : "display:none;";
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

                    pushToast(payload) {
                        const id = Date.now();

                        this.toasts.unshift({
                            id,
                            title: "Nueva solicitud de material",
                            message: `Material: ${payload.material.descripcion} (${payload.material.cantidad})`,
                            time: new Date().toLocaleTimeString()
                        });

                        // auto cerrar en 6 segundos
                        setTimeout(() => {
                            this.removeToast(id);
                        }, 6000);
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
                    }

                }
            }).mount("#app");

        });
    </script>
@endpush
