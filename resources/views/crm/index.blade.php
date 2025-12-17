@extends('layouts.app')

@section('content')
    <div id="crmApp" class="space-y-4">

        <h2 class="text-lg font-semibold">Seguimiento CRM</h2>

        {{-- Filtros --}}
        <div class="flex flex-wrap gap-2 items-end bg-zinc-50 p-3 rounded border">

            <div>
                <label class="text-xs">Desde</label>
                <input type="date" v-model="filters.start" class="h-8 border rounded px-2 text-xs">
            </div>

            <div>
                <label class="text-xs">Hasta</label>
                <input type="date" v-model="filters.end" class="h-8 border rounded px-2 text-xs">
            </div>

            @if (in_array(auth()->user()->perfil_usuario_id, [1, 2]))
                <div>
                    <label class="text-xs">Asesor</label>
                    <input type="text" v-model="filters.asesor" placeholder="username"
                        class="h-8 border rounded px-2 text-xs">
                </div>
            @endif

            <button @click="cargarDatos" class="h-8 px-4 bg-black text-white text-xs rounded">
                Consultar
            </button>

            <input type="text" v-model="search" placeholder="Buscar cliente, oportunidad o actividad"
                class="h-8 flex-1 border rounded px-2 text-xs">
        </div>

        {{-- Tabla --}}
        <div class="overflow-x-auto border rounded">
            <table class="w-full text-xs">
                <thead class="bg-zinc-100">
                    <tr>
                        <th class="px-2 py-1">Fecha</th>
                        <th class="px-2 py-1">Oportunidad</th>
                        <th class="px-2 py-1">Cliente</th>
                        <th class="px-2 py-1">Asesor</th>
                        <th class="px-2 py-1">Actividad</th>
                        <th class="px-2 py-1">Fecha Actividad</th>
                        <th class="px-2 py-1">Observación</th>
                    </tr>
                </thead>

                <tbody>
                    <template v-for="(row, index) in rows" :key="`${row.oportunidad}-${index}`">

                        {{-- FILA PRINCIPAL --}}
                        <tr class="border-b hover:bg-zinc-50">
                            <td>@{{ row.fechaRegistro }}</td>
                            <td>@{{ row.oportunidad }}</td>

                            <td>
                                <button class="mr-1 text-blue-600 font-bold" @click="toggle(row.oportunidad)">
                                    @{{ expanded === row.oportunidad ? '−' : '+' }}
                                </button>
                                @{{ row.cliente }}
                            </td>

                            <td>@{{ row.asesor }}</td>
                            <td>@{{ row.actividad }}</td>
                            <td>@{{ row.fechaActividad }}</td>
                            <td>@{{ row.observacion }}</td>
                        </tr>

                        {{-- FILA DESPLEGABLE --}}
                        <tr v-if="expanded === row.oportunidad" class="bg-zinc-50 border-b">
                            <td colspan="7" class="px-6 py-4">
                                <div class="bg-white border rounded-md p-4 text-[11px] shadow-sm">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-y-2 gap-x-6">
                                        <div><strong>Teléfono:</strong> @{{ row.telefono }}</div>
                                        <div><strong>Celular:</strong> @{{ row.celular }}</div>
                                        <div><strong>Email:</strong> @{{ row.email }}</div>
                                        <div><strong>Contacto:</strong> @{{ row.contacto }}</div>
                                        <div class="md:col-span-2">
                                            <strong>Dirección:</strong> @{{ row.direccion }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    </template>

                    <tr v-if="rows.length === 0">
                        <td colspan="7" class="text-center py-6 text-zinc-500">
                            Sin información
                        </td>
                    </tr>
                </tbody>
            </table>

            <nav class="mt-3">
                <ul class="pagination pagination-sm justify-content-center mb-0">

                    <li class="page-item" :class="{ disabled: page === 1 }">
                        <a class="page-link" href="#" @click.prevent="page > 1 && (page--, cargarDatos())">
                            Anterior
                        </a>
                    </li>

                    <li class="page-item disabled">
                        <span class="page-link">
                            Página @{{ page }} de @{{ lastPage }}
                        </span>
                    </li>

                    <li class="page-item" :class="{ disabled: page === lastPage }">
                        <a class="page-link" href="#" @click.prevent="page < lastPage && (page++, cargarDatos())">
                            Siguiente
                        </a>
                    </li>

                </ul>
            </nav>

        </div>

    </div>
@endsection



@push('scripts')
    <script>
        const {
            createApp
        } = Vue;

        createApp({
            data() {
                return {
                    rows: [],
                    search: '',
                    expanded: null,
                    filters: {
                        start: null,
                        end: null,
                        asesor: ''
                    },
                    page: 1,
                    perPage: 50,
                    lastPage: 1
                }
            },

            computed: {
                filteredRows() {
                    if (!this.search) return this.rows;

                    const s = this.search.toLowerCase();
                    return this.rows.filter(r =>
                        `${r.oportunidad} ${r.cliente} ${r.actividad}`
                        .toLowerCase()
                        .includes(s)
                    );
                }
            },

            methods: {

                toggle(id) {
                    this.expanded = this.expanded === id ? null : id;
                },

                async cargarDatos() {

                    const params = new URLSearchParams();

                    params.append('page', this.page);
                    params.append('per_page', this.perPage);

                    if (this.filters.start) {
                        params.append('start', this.filters.start);
                    }

                    if (this.filters.end) {
                        params.append('end', this.filters.end);
                    }

                    if (this.filters.asesor) {
                        params.append('asesor', this.filters.asesor);
                    }

                    if (this.search) {
                        params.append('search', this.search);
                    }

                    const res = await fetch(
                        `{{ route('portal-crm.seguimiento.data') }}?${params.toString()}`
                    );

                    const json = await res.json();

                    if (json.success) {
                        this.rows = json.data;
                        this.lastPage = json.pagination.last_page;
                    }
                }
            },

            mounted() {
                this.cargarDatos();
            }
        }).mount('#crmApp');
    </script>
@endpush
