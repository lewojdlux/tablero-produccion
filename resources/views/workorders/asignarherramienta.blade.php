@extends('layouts.app')

@section('content')
    <style>
        [v-cloak] {
            display: none;
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div id="asignar-herramienta-app" v-cloak data-order-id="{{ $orderId }}"
        data-materials-url="{{ route('workorders.materials', ['id' => $orderId]) }}"
        data-add-url="{{ route('workorders.materials.asignar', ['workorder' => $orderId]) }}"
        data-pedido-url="{{ route('pedidos.materiales.asignar', ['pedido' => $orderId]) }}"
        class="p-4">

        {{-- Detalle orden --}}
        <div class="mb-3">
            <a href="{{ route('ordenes.trabajo.asignados') }}" class="btn btn-sm btn-dark "><i class="fa-solid fa-arrow-left"
                    title="Volver a la sordenes de trabajo"></i></a>
            <dl class="row">
                <dt class="col-3">Pedido</dt>
                <dd class="col-9">{{ $dataAsignarMaterial['pedido'] ?? '—' }}</dd>

                <dt class="col-3">Cliente</dt>
                <dd class="col-9">{{ $dataAsignarMaterial['tercero'] ?? '—' }}</dd>

                <dt class="col-3">Asesor</dt>
                <dd class="col-9">{{ $dataAsignarMaterial['vendedor'] ?? '—' }}</dd>
            </dl>
        </div>

        {{-- Buscador autosuggest --}}
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="position-relative" style="width:420px">
                <input v-model="query" @input="onInput" @keydown.arrow-down.prevent="focusNext"
                    @keydown.arrow-up.prevent="focusPrev" @keydown.enter.prevent="selectFocused" class="form-control"
                    placeholder="Buscar herramienta por nombre o código...">

                <ul v-if="suggestions.length && showSuggestions" class="list-group position-absolute w-100 mt-1 shadow"
                    style="z-index:1055; max-height:280px; overflow:auto;">

                    <li v-for="s in suggestions" :key="s.id" class="list-group-item">

                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">@{{ s.nombre }}</div>
                                <div class="small text-muted">@{{ s.codigo }}</div>
                            </div>

                            <input type="number" min="1" v-model.number="s._cantidad"
                                class="form-control form-control-sm" style="width:80px">

                            <button class="btn btn-sm btn-success" @click="toggleSeleccion(s)">
                                @{{ s._selected ? 'Quitar' : 'Añadir' }}
                            </button>
                        </div>
                    </li>

                    <li class="list-group-item text-center bg-light">
                        <button class="btn btn-primary btn-sm" :disabled="!selectedItems.length" @click="addSeleccionados">
                            Agregar seleccionados (@{{ selectedItems.length }})
                        </button>
                    </li>
                </ul>
            </div>

            <input type="number" v-model.number="cantidad" min="1" class="form-control w-auto ms-2"
                style="width:90px">

            <button v-if="query && query.length >= 2 && !showSuggestions" @click="openPedidoModal({ codigo: query })"
                class="btn btn-warning ms-2">Reportar: "@{{ query }}"</button>

            <button @click="openPedidoModal({})" class="btn btn-outline-primary ms-2">Solicitar material</button>
        </div>

        {{-- Tabla de herramientas asignadas --}}
        <div class="table-responsive border rounded p-2 bg-white">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Cantidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in herramientas" :key="item.id_work_order_material">
                        <td>@{{ item.codigo_material ?? '—' }}</td>
                        <td>@{{ item.nombre_material }}</td>
                        <td>@{{ item.cantidad }}</td>
                        <td><button class="btn btn-sm btn-danger" @click="removeSelected(item)">Eliminar</button></td>
                    </tr>
                    <tr v-if="!herramientas.length">
                        <td colspan="4" class="text-center text-muted">No hay herramientas asignadas.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal (Bootstrap) - colocamos aquí pero lo movemos al body en mounted() -->
        <div id="pedidoModalBootstrap" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Solicitud de material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <form id="pedidoForm" @submit.prevent="submitPedido">
                        <input type="hidden" name="orden_trabajo_id" v-model="pedidoForm.orden_trabajo_id"
                            data-order-id="{{ $orderId }}">

                        <div class="modal-body">
                            <div class="mb-2">
                                <label class="form-label small">Código (si lo conoces)</label>
                                <input v-model="pedidoForm.codigo" type="text" class="form-control form-control-sm">
                            </div>

                            <div class="mb-2">
                                <label class="form-label small">Nombre / Descripción</label>
                                <input v-model="pedidoForm.nombre" required type="text"
                                    class="form-control form-control-sm">
                            </div>

                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label small">Cantidad</label>
                                    <input v-model.number="pedidoForm.cantidad" type="number" min="1"
                                        class="form-control form-control-sm">
                                </div>
                                <div class="col">
                                    <label class="form-label small">Observación</label>
                                    <input v-model="pedidoForm.observacion" type="text"
                                        class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" :disabled="pedidoSubmitting" class="btn btn-primary btn-sm">
                                <span v-if="!pedidoSubmitting">Enviar solicitud</span>
                                <span v-else>Enviando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>



        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:3000">
            <div v-for="(toast, index) in toasts"
                :key="index"
                class="toast show shadow mb-2"
                role="alert">

                <div class="toast-header">
                    <strong class="me-auto">@{{ toast.title }}</strong>
                    <small class="text-muted">ahora</small>
                    <button type="button" class="btn-close"
                            @click="removeToast(index)"></button>
                </div>

                <div class="toast-body">
                    @{{ toast.message }}
                </div>
            </div>
        </div>



        <div class="toast-container position-fixed top-0 end-0 p-3"
            style="z-index: 3000"
            id="toast-container">
        </div>

    </div>


    <!-- JS: Vue 3 (CDN) y Bootstrap JS si tu layout NO lo incluye -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <!-- Si tu layout ya carga bootstrap.js no incluyas la siguiente línea -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function() {
            const {
                createApp
            } = Vue;

            createApp({
                data() {
                    return {
                        query: '',
                        toasts: [],
                        suggestions: [],
                        showSuggestions: false,
                        focusedIndex: -1,
                        selected: null,
                        cantidad: 1,
                        herramientas: @json($dataAsignarMaterialHerramienta ?? []),
                        debounceTimer: null,
                        orderId: null,
                        // modal
                        pedidoModalInstance: null,
                        showPedidoModal: false, // solo para control local
                        pedidoSubmitting: false,
                        pedidoForm: {
                            orden_trabajo_id: null,
                            codigo: '',
                            nombre: '',
                            cantidad: 1,
                            observacion: ''
                        }
                    };
                },
                mounted() {
                    const root = document.getElementById('asignar-herramienta-app');
                    this.orderId = root ? root.dataset.orderId || null : null;

                    this.pedidoForm.orden_trabajo_id = this.orderId;

                    const modalEl = document.getElementById('pedidoModalBootstrap');
                    if (modalEl) {
                        this.pedidoModalInstance = new bootstrap.Modal(modalEl, {
                            backdrop: true,
                            keyboard: true
                        });
                    }

                    // 🔥 CARGAR MATERIALES ASIGNADOS AL INICIAR
                    this.refreshHerramientas();


                    if (window.Echo) {
                        window.Echo.private('admin-channel')
                            .listen('.material.solicitado', (payload) => {
                                this.pushToast(payload);
                            });
                    }
                },

                computed: {
                    selectedItems() {
                        return this.suggestions.filter(s => s._selected);
                    }
                },

                methods: {
                    onInput() {
                        this.selected = null;
                        this.focusedIndex = -1;
                        if (this.debounceTimer) clearTimeout(this.debounceTimer);
                        if (!this.query || this.query.length < 2) {
                            this.suggestions = [];
                            this.showSuggestions = false;
                            return;
                        }
                        this.debounceTimer = setTimeout(() => this.fetchSuggestions(), 300);
                    },
                    async fetchSuggestions() {
                        try {
                            const url = new URL("{{ route('herramientas.search') }}", window.location
                                .origin);
                            url.searchParams.set('q', this.query);
                            const res = await fetch(url.toString(), {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });
                            if (!res.ok) {
                                this.suggestions = [];
                                this.showSuggestions = false;
                                return;
                            }
                            const data = await res.json();
                            let items = Array.isArray(data) ? data : (data?.data ?? []);
                            this.suggestions = items.map(x => ({
                                id: x.id_material ?? x.id,
                                nombre: x.nombre_material ?? x.nombre ?? '',
                                codigo: x.codigo_material ?? x.codigo ?? '',
                                _cantidad: 1,
                                _selected: false
                            }));
                            this.showSuggestions = this.suggestions.length > 0;
                        } catch (err) {
                            console.error(err);
                            this.suggestions = [];
                            this.showSuggestions = false;
                        }
                    },
                    selectSuggestion(s) {
                        this.selected = s;
                        // agregar inmediatamente
                        this.addSelected(true);
                    },

                    async addSelected(fromClick = false) {
                        if (!this.selected) return;

                        if (!this.orderId) {
                            alert('No se encontró el id de la orden.');
                            return;
                        }

                        const existente = this.herramientas.find(
                            h => (h.id_material ?? h.id) === this.selected.id
                        );

                        const payload = {
                            herramienta_id: this.selected.id,
                            cantidad: this.cantidad
                        };

                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.content;
                            const root = document.getElementById('asignar-herramienta-app');
                            const addBase = root.dataset.addUrl.replace(/\/$/, '');
                            const addUrl = `${addBase}/${this.orderId}`;

                            const res = await fetch(addUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token
                                },
                                body: JSON.stringify(payload)
                            });

                            const json = await res.json();

                            if (!res.ok || !json.success) {
                                alert(json?.message || 'Error al agregar material');
                                return;
                            }

                            // 🔁 SI YA EXISTE → SUMA
                            if (existente) {
                                existente.cantidad += this.cantidad;
                            } else {
                                await this.refreshHerramientas();
                            }

                            // limpiar SOLO lo necesario
                            this.selected = null;
                            this.query = '';
                            this.suggestions = [];
                            this.showSuggestions = false;
                            this.cantidad = 1;

                        } catch (e) {
                            console.error(e);
                            alert('Error al comunicarse con el servidor');
                        }
                    },


                    focusNext() {
                        if (!this.suggestions.length) return;
                        this.focusedIndex = (this.focusedIndex + 1) % this.suggestions.length;
                    },
                    focusPrev() {
                        if (!this.suggestions.length) return;
                        this.focusedIndex = (this.focusedIndex - 1 + this.suggestions.length) % this.suggestions
                            .length;
                    },
                    selectFocused() {
                        if (this.focusedIndex >= 0) {
                            this.selected = this.suggestions[this.focusedIndex];
                            this.addSelected();
                        }
                    },


                    async removeSelected(item) {
                        if (!item || !item.id) return;
                        if (!confirm('¿Eliminar este material de la orden?')) return;
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || '';
                            const root = document.getElementById('asignar-herramienta-app');
                            const deleteBase = root ? root.dataset.deleteBase || '' : '';
                            const deleteUrl = deleteBase.replace(/\/$/, '') + '/' + encodeURIComponent(this
                                .orderId) + '/materials/' + encodeURIComponent(item.id);
                            const res = await fetch(deleteUrl, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token
                                }
                            });
                            if (!res.ok) {
                                const text = await res.text();
                                return alert('No se pudo eliminar (ver consola).');
                            }
                            this.herramientas = this.herramientas.filter(h => (h.id ?? h.id_material) !==
                                item.id);
                        } catch (e) {
                            console.error(e);
                            alert('Error al comunicarse con el servidor.');
                        }
                    },

                    /* ----- Modal (Bootstrap) ----- */
                    openPedidoModal(prefill = {}) {
                        this.pedidoForm.orden_trabajo_id = this.orderId;
                        this.pedidoForm.codigo = prefill.codigo ?? '';
                        this.pedidoForm.nombre = prefill.nombre ?? '';
                        this.pedidoForm.cantidad = prefill.cantidad ?? 1;
                        this.pedidoForm.observacion = prefill.observacion ??
                            `Solicitud desde orden ${this.orderId}`;

                        // actualizar value del input hidden (por si el modal no está dentro de Vue)
                        const modalEl = document.getElementById('pedidoModalBootstrap');
                        if (modalEl) {
                            const hidden = modalEl.querySelector('input[name="orden_trabajo_id"]');
                            if (hidden) hidden.value = this.pedidoForm.orden_trabajo_id ?? '';
                            this.pedidoModalInstance?.show();
                        }
                    },
                    closePedidoModal() {
                        if (this.pedidoModalInstance) this.pedidoModalInstance.hide();
                    },

                    async submitPedido() {

                        if (!this.pedidoForm.nombre || !this.pedidoForm.nombre.trim()) {
                            return alert('Ingrese nombre o descripción del material.');
                        }
                        if (!this.orderId) return alert('Orden no encontrada.');

                        this.pedidoSubmitting = true;
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || '';
                            const root = document.getElementById('asignar-herramienta-app');
                            const pedidoBase = root ? root.dataset.pedidoBase || '' : '';
                            const url = root.dataset.pedidoUrl;

                            const payload = {
                                _token: token,
                                orden_trabajo_id: this.orderId,
                                codigo_material: this.pedidoForm.codigo || null,
                                nombre_material: this.pedidoForm.nombre,
                                cantidad: this.pedidoForm.cantidad,
                                observacion: this.pedidoForm.observacion
                            };


                            const res = await fetch(url, {
                                method: 'POST',
                                credentials: 'same-origin', // 🔥 CLAVE ABSOLUTA
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify(payload)
                            });

                            const json = await res.json().catch(() => null);

                            if (!res.ok) {
                                alert(json?.message || 'Error al registrar solicitud. Revisa consola.');
                                return;
                            }

                            this.query = '';
                            this.selected = null;
                            this.suggestions = [];

                            alert('Solicitud registrada correctamente.');
                            this.pedidoModalInstance?.hide();



                        } catch (e) {
                            console.error(e);
                            alert('Error al comunicarse con el servidor.');
                        } finally {
                            this.pedidoSubmitting = false;
                        }
                    },
                    resetPedidoForm() {
                        this.pedidoForm = {
                            orden_trabajo_id: this.orderId,
                            codigo: '',
                            nombre: '',
                            cantidad: 1,
                            observacion: ''
                        };
                    },

                    toggleSeleccion(item) {
                        item._selected = !item._selected;
                    },

                    async addSeleccionados() {
                        if (!this.orderId || !this.selectedItems.length) return;

                        const token = document.querySelector('meta[name="csrf-token"]').content;
                        const root = document.getElementById('asignar-herramienta-app');
                        const addBase = root.dataset.addUrl.replace(/\/$/, '');
                        const url = `${addBase}/${this.orderId}`;

                        for (const item of this.selectedItems) {

                            const existente = this.herramientas.find(
                                h => (h.id_material ?? h.id) === item.id
                            );

                            const payload = {
                                herramienta_id: item.id,
                                cantidad: item._cantidad || 1
                            };

                            try {
                                const res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': token
                                    },
                                    body: JSON.stringify(payload)
                                });

                                const json = await res.json();

                                if (!res.ok || !json.success) {
                                    console.error(json?.message || 'Error al agregar');
                                    continue;
                                }

                                if (existente) {
                                    existente.cantidad += payload.cantidad;
                                } else {
                                    await this.refreshHerramientas();
                                }

                            } catch (e) {
                                console.error(e);
                            }
                        }

                        // limpiar estado visual
                        this.suggestions = [];
                        this.query = '';
                        this.showSuggestions = false;
                    },

                    async refreshHerramientas() {
                        const root = document.getElementById('asignar-herramienta-app');
                        const url = root.dataset.materialsUrl;

                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        this.herramientas = await res.json();
                    },


                    pushToast(payload) {
                        this.toasts.unshift({
                            title: payload.title,
                            message: payload.message
                        });

                        // Auto cerrar
                        setTimeout(() => {
                            this.toasts.pop();
                        }, 8000);
                    },

                    removeToast(index) {
                        this.toasts.splice(index, 1);
                    }

                }
            }).mount('#asignar-herramienta-app');
        })();
    </script>
@endsection
