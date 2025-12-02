@extends('layouts.app')

@section('content')
    <!-- Asegúrate de tener Bootstrap CSS en tu layout; si no, agrega:
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div id="asignar-herramienta-app" data-order-id="{{ $orderId ?? ($dataAsignarMaterial['id_work_order'] ?? '') }}"
        data-add-url="{{ url('registrar/herramienta') }}" data-delete-base="{{ url('workorders') }}"
        data-pedido-base="{{ url('workorders') }}" class="p-4">

        {{-- Detalle orden --}}
        <div class="mb-3">
            <a href="{{ route('asignar.material.index') }}" class="btn btn-sm btn-dark " ><i class="fa-solid fa-arrow-left" title="Volver a la sordenes de trabajo" ></i></a>
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
                    style="z-index:1055; max-height:220px; overflow:auto;">
                    <li v-for="(s, idx) in suggestions" :key="s.id"
                        class="list-group-item list-group-item-action" :class="focusedIndex === idx ? 'active' : ''"
                        @mouseenter="focusedIndex = idx" @mouseleave="focusedIndex = -1" @click="selectSuggestion(s)">
                        <div class="fw-semibold">@{{ s.nombre }}</div>
                        <div class="small text-muted">@{{ s.codigo }}</div>
                    </li>
                </ul>
            </div>

            <input type="number" v-model.number="cantidad" min="1" class="form-control w-auto ms-2"
                style="width:90px">
            <button class="btn btn-success ms-2" @click="addSelected" :disabled="!selected">Agregar</button>

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
                    <tr v-for="item in herramientas" :key="item.id">
                        <td>@{{ item.codigo ?? '—' }}</td>
                        <td>@{{ item.nombre }}</td>
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

                    // asegurar valor inicial del hidden (v-model)
                    this.pedidoForm.orden_trabajo_id = this.orderId;

                    // inicializar modal bootstrap SIN moverlo al body (si ya lo moviste, ver nota abajo)
                    const modalEl = document.getElementById('pedidoModalBootstrap');
                    if (modalEl) {
                        this.pedidoModalInstance = new bootstrap.Modal(modalEl, {
                            backdrop: true,
                            keyboard: true
                        });

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
                                id: x.id_material ?? x.id ?? null,
                                nombre: x.nombre_material ?? x.nombre ?? '',
                                codigo: x.codigo_material ?? x.codigo ?? '',
                                cantidad: x.cantidad ?? 0
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
                        this.query = s.nombre + (s.codigo ? ' — ' + s.codigo : '');
                        this.showSuggestions = false;

                        this.pedidoForm.orden_trabajo_id = this.orderId; // <- importante
                        this.pedidoForm.codigo = s.codigo ?? '';
                        this.pedidoForm.nombre = s.nombre ?? '';
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
                        if (this.focusedIndex >= 0 && this.suggestions[this.focusedIndex]) this
                            .selectSuggestion(this.suggestions[this.focusedIndex]);
                    },

                    async addSelected() {
                        if (!this.selected) return alert('Selecciona una herramienta primero.');
                        if (!this.orderId) return alert('No se encontró el id de la orden.');
                        const payload = {
                            herramienta_id: this.selected.id,
                            cantidad: this.cantidad
                        };
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || '';
                            const root = document.getElementById('asignar-herramienta-app');
                            const addBase = root ? root.dataset.addUrl || '' : '';
                            const addUrl = addBase.replace(/\/$/, '') + '/' + encodeURIComponent(this
                                .orderId);
                            const res = await fetch(addUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token
                                },
                                body: JSON.stringify(payload)
                            });
                            if (!res.ok) {
                                const err = await res.json().catch(() => null);
                                return alert('Error al agregar. Revisa la consola.');
                            }
                            const json = await res.json();
                            if (json.success) {
                                this.herramientas.unshift(json.item);
                                this.query = '';
                                this.selected = null;
                                this.suggestions = [];
                                this.cantidad = 1;
                            } else alert('No se pudo agregar la herramienta.');
                        } catch (e) {
                            console.error(e);
                            alert('Error al comunicarse con el servidor.');
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
                            const url = pedidoBase.replace(/\/$/, '') + '/' + encodeURIComponent(this
                                .orderId) + '/pedido-material';

                            const payload = {
                                orden_trabajo_id: this.pedidoForm.orden_trabajo_id || this.orderId,
                                codigo: this.pedidoForm.codigo ? this.pedidoForm.codigo
                                    .trim() : null,
                                nombre: this.pedidoForm.nombre.trim(),
                                cantidad: this.pedidoForm.cantidad || 1,
                                observacion: this.pedidoForm.observacion ? this.pedidoForm.observacion
                                    .trim() : null
                            };


                            const res = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token
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
                }
            }).mount('#asignar-herramienta-app');
        })();
    </script>
@endsection
