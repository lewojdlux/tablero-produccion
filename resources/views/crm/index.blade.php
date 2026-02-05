@extends('layouts.app')

@section('content')
    <style>
        [v-cloak] {
            display: none;
        }
    </style>
    <div id="crmApp" v-cloak class="space-y-4">

        <h2 class="text-lg font-semibold">Seguimiento CRM</h2>

        <transition name="fade">
            <div v-if="mostrarGlobal" class="grid grid-cols-2 md:grid-cols-4 gap-3 my-3">

                <div class="bg-white border rounded p-3">
                    <div class="text-xs text-zinc-500">Eventos</div>
                    <div class="text-xl font-semibold text-indigo-600">
                        @{{ totales.total_oportunidades }}
                    </div>
                </div>

                <div class="bg-white border rounded p-3">
                    <div class="text-xs text-zinc-500">Actividades</div>
                    <div class="text-xl font-semibold text-indigo-600">
                        @{{ totales.total_actividades }}
                    </div>
                </div>

                <div class="bg-white border rounded p-3">
                    <div class="text-xs text-zinc-500">Asesores</div>
                    <div class="text-xl font-semibold">
                        @{{ filters.asesores.length || 'Todos' }}
                    </div>
                </div>

                <div class="bg-white border rounded p-3">
                    <div class="text-xs text-zinc-500">Periodo</div>
                    <div class="text-sm font-medium">
                        @{{ filters.start }} → @{{ filters.end }}
                    </div>
                </div>

            </div>
        </transition>


        <div v-if="mostrarResumenAsesor" class="mt-4 border rounded">
            <div class="text-xl font-semibold ">
                Resumen por asesor
            </div>
            <table class="w-full text-xs">
                <thead class="bg-zinc-100">
                    <tr>
                        <th class="px-2 py-1">Asesor</th>
                        <th class="px-2 py-1 text-center">Eventos</th>
                        <th class="px-2 py-1 text-center">Actividades</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in totalesPorAsesor" :key="t.asesor">
                        <td class="px-2 py-1 font-medium">
                            @{{ t.asesor }}
                        </td>
                        <td class="px-2 py-1 text-center">
                            @{{ t.eventos }}
                        </td>
                        <td class="px-2 py-1 text-center">
                            @{{ t.actividades }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


        <div v-if="errorExport"
            class="bg-red-100 border border-danger border-red-400 text-red-700 px-4 py-2 rounded text-xs mb-2 flex justify-between items-center">
            <span>@{{ errorExport }}</span>
            <button @click="errorExport = ''" class="font-bold ml-3">✕</button>
        </div>


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

            @if (in_array(auth()->user()->perfil_usuario_id, [1, 2, 9]))
                <div class="relative">

                    <button @click="showAsesores = !showAsesores"
                        class="h-8 px-3 border rounded text-xs bg-white hover:bg-zinc-100 flex items-center gap-2">

                        👤 Asesores
                        <span v-if="filters.asesores.length" class="bg-indigo-600 text-white rounded-full px-2 text-[10px]">
                            @{{ filters.asesores.length }}
                        </span>
                    </button>

                    <!-- PANEL -->
                    <div v-if="showAsesores"
                        class="absolute top-full right-0 mt-2 w-[520px] bg-white border rounded-xl shadow-2xl z-[9999]">

                        <!-- HEADER -->
                        <div class="px-4 py-2 border-b bg-zinc-50 rounded-t-xl">
                            <div class="text-xs font-semibold text-zinc-800">
                                Seleccionar asesores
                            </div>
                            <div class="text-[11px] text-zinc-500">
                                Puede seleccionar uno o varios
                            </div>
                        </div>

                        <!-- LISTA EN COLUMNAS -->
                        <div class="px-4 py-3">
                            <div
                                class="grid grid-flow-col grid-rows-[repeat(5,minmax(0,1fr))] gap-x-6 gap-y-2 max-h-56 overflow-y-auto">

                                <label v-for="a in asesoresDisponibles" :key="a.Usuario"
                                    class="flex items-center gap-2 text-xs text-zinc-700 hover:bg-zinc-100 px-2 py-1 rounded cursor-pointer">

                                    <input type="checkbox" :value="a.Usuario" v-model="filters.asesores"
                                        class="accent-indigo-600">

                                    <span class="truncate max-w-[160px]">
                                        @{{ a.Nombre }}
                                    </span>
                                </label>

                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="flex items-center justify-between px-4 py-2 border-t bg-zinc-50 rounded-b-xl">

                            <button @click="filters.asesores = []" class="text-xs text-red-600 hover:underline">
                                Limpiar
                            </button>

                            <button @click="showAsesores = false; cargarDatos()"
                                class="text-xs bg-black text-white px-4 py-1.5 rounded-md">
                                Aplicar
                            </button>
                        </div>
                    </div>

                </div>
            @endif

            <div>
                <label class="text-xs">Sector</label>
                <select v-model="filters.sector" class="h-8 border rounded px-2 text-xs">
                    <option value="">Todos</option>
                    <option value="0">General</option>
                    <option value="1">Industrial</option>
                    <option value="2">Comercial</option>
                    <option value="3">Residencial</option>
                </select>
            </div>


            <!--<div>
                <label class="text-xs">Estado</label>
                <select v-model="filters.estado" class="h-8 border rounded px-2 text-xs">
                    <option value="">Todos</option>
                    <option v-for="e in estadosDisponibles" :value="e.IdEstado" >
                        @{{ e.EstadoOportunidad }}
                    </option>
                </select>
            </div>-->


            <!--<div>
                <label class="text-xs">Resultado</label>
                <select v-model="filters.resultado" class="h-8 border rounded px-2 text-xs">
                    <option value="">Todos</option>
                    <option v-for="r in resultadosDisponibles" :value="r.id">
                        @{{ r.nombre }}
                    </option>
                </select>
            </div>-->

            <!--<div>
                <label class="text-xs">Fuente</label>
                <select v-model="filters.fuente" class="h-8 border rounded px-2 text-xs">
                    <option value="">Todas</option>
                    <option v-for="f in fuentesDisponibles" :value="f.id">
                        @{{ f.nombre }}
                    </option>
                </select>
            </div>-->


            <button @click="cargarDatos" class="h-8 px-4 bg-black text-white text-xs rounded">
                Consultar
            </button>


            <button @click="exportarExcel" title="Exportar datos a excel"
                class="h-8 px-4 bg-green-600 text-white text-xs rounded"> 📥 Exportar Excel
            </button>

            <input type="text" v-model="search" placeholder="Buscar cliente, oportunidad o actividad"
                class="h-8 flex-1 border rounded px-2 text-xs">
        </div>




        {{-- TABLA --}}
        <div class="overflow-x-auto border rounded bg-white">
            <table class="w-full text-xs">
                <thead class="bg-zinc-100 text-zinc-700">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Oportunidad</th>
                        <th class="px-3 py-2">Cliente</th>
                        <th class="px-3 py-2">Asesor</th>
                        <th class="px-3 py-2 text-center">Actividades</th>
                    </tr>
                </thead>

                <tbody>
                    <template v-for="op in oportunidadesAgrupadas" :key="op.oportunidad"
                        v-show="expanded === null || expanded === op.oportunidad">

                        <!-- ================= FILA PRINCIPAL ================= -->
                        <tr class="border-b hover:bg-zinc-50">
                            <td class="px-3 py-1.5">@{{ op.fechaRegistro }}</td>

                            <td class="px-3 py-1.5 font-semibold text-indigo-700">
                                @{{ op.oportunidad }}
                            </td>

                            <td class="px-3 py-1.5">
                                <button class="mr-1 text-indigo-600 font-bold" @click="toggle(op.oportunidad)">
                                    @{{ expanded === op.oportunidad ? '−' : '+' }}
                                </button>
                                @{{ op.cliente }}
                            </td>

                            <td class="px-3 py-1.5">@{{ op.asesor }}</td>

                            <td class="px-3 py-1.5 text-center">
                                <span
                                    class="inline-flex items-center justify-center
                         rounded-full bg-indigo-100 text-indigo-700
                         px-2 py-0.5 text-[11px] font-semibold">
                                    @{{ op.actividades.length }}
                                </span>
                            </td>
                        </tr>

                        <!-- ================= DETALLE EXPANDIDO ================= -->
                        <tr v-if="expanded === op.oportunidad" class="bg-zinc-50 border-b" :data-op="op.oportunidad">
                            <td colspan="7" class="px-5 py-4">

                                <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4 text-sm">

                                    <!-- VOLVER -->
                                    <div class="flex justify-between items-center border-b pb-2">
                                        <button class="text-xs text-indigo-600 hover:underline" @click="expanded = null">
                                            ← Volver al listado
                                        </button>

                                        <span class="text-[11px] text-zinc-500">
                                            Oportunidad #@{{ op.oportunidad }}
                                        </span>
                                    </div>

                                    <!-- HEADER -->
                                    <div class="flex flex-wrap justify-between items-center gap-2">
                                        <div>
                                            <h3 class="text-sm font-semibold text-indigo-700">
                                                Oportunidad #@{{ op.oportunidad }}
                                            </h3>
                                            <p class="text-[11px] text-zinc-500">
                                                @{{ op.cliente }} · Asesor: @{{ op.asesor }}
                                            </p>
                                        </div>

                                        <span
                                            class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-medium">
                                            @{{ op.estado }}
                                        </span>
                                    </div>

                                    <!-- ================= RESUMEN ================= -->
                                    <div class="bg-zinc-50 border rounded-md p-3">
                                        <p class="text-[11px] text-zinc-500 mb-1 uppercase tracking-wide">
                                            Resumen de la oportunidad
                                        </p>
                                        <p class="text-zinc-800 leading-snug text-sm">
                                            @{{ op.actividades[0]?.observacion || 'Sin observaciones' }}
                                        </p>
                                    </div>

                                    <!-- ================= 1. CONTACTO ================= -->
                                    <div class="border-t pt-3">
                                        <h4 class="text-sm font-semibold mb-2">Contacto</h4>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-2 text-xs">
                                            <div>
                                                <span class="text-zinc-500">Nombre</span>
                                                <p class="font-medium">@{{ op.contacto }}</p>
                                            </div>
                                            <div>
                                                <span class="text-zinc-500">Teléfono</span>
                                                <p>@{{ op.telefono || '-' }}</p>
                                            </div>
                                            <div>
                                                <span class="text-zinc-500">Celular</span>
                                                <p>@{{ op.celular || '-' }}</p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-zinc-500">Email</span>
                                                <p>@{{ op.email || '-' }}</p>
                                            </div>
                                            <div class="md:col-span-3">
                                                <span class="text-zinc-500">Dirección</span>
                                                <p>@{{ op.direccion || '-' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ================= 2. INTERÉS ================= -->
                                    <div class="border-t pt-3">
                                        <h4 class="text-sm font-semibold mb-2">Interés del cliente</h4>
                                        <p class="font-medium">@{{ op.interes }}</p>
                                    </div>

                                    <!-- ================= 3. SECTOR / CONTEXTO ================= -->
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2 border-t pt-3">
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Sector</p>
                                            <p class="font-medium">@{{ op.sector }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Ciudad</p>
                                            <p class="font-medium">@{{ op.ciudad }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Gremio</p>
                                            <p class="font-medium">@{{ op.gremio }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Proveedor actual</p>
                                            <p>@{{ op.proveedorActual }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Proveedor competidor</p>
                                            <p>@{{ op.proveedorCompetidor }}</p>
                                        </div>
                                    </div>

                                    <!-- ================= 4. ESTADO / ETAPA / AVANCE ================= -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-2 border-t pt-3">
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Estado oportunidad</p>
                                            <p class="font-medium">@{{ op.estado }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Etapa / Avance</p>
                                            <p class="font-medium">@{{ op.etapa }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Resultado</p>
                                            <p class="font-medium">@{{ op.resultado }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Causa</p>
                                            <p class="font-medium">@{{ op.causa }}</p>
                                        </div>
                                    </div>

                                    <!-- ================= 5. NEGOCIO ================= -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-2 border-t pt-3">
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Presupuesto</p>
                                            <p class="font-semibold text-green-600">
                                                $@{{ Number(op.presupuesto).toLocaleString() }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Fecha cierre</p>
                                            <p class="font-medium">@{{ op.fechaCierre }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-zinc-500 uppercase">Fuente</p>
                                            <p class="font-medium">@{{ op.fuente }}</p>
                                        </div>
                                    </div>

                                    <!-- ================= 6. ACTIVIDADES ================= -->
                                    <div class="border-t pt-3">
                                        <h4 class="text-sm font-semibold mb-2">
                                            Actividades y seguimiento
                                        </h4>

                                        <table class="w-full text-[11px] border">
                                            <thead class="bg-zinc-100">
                                                <tr>
                                                    <th class="px-2 py-0.5 text-left">Fecha</th>
                                                    <th class="px-2 py-0.5 text-left">Actividad</th>
                                                    <th class="px-2 py-0.5 text-left">Detalle</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(a, i) in op.actividades" :key="i"
                                                    class="border-t">
                                                    <td class="px-2 py-0.5">@{{ a.fecha }}</td>
                                                    <td class="px-2 py-0.5 font-medium">@{{ a.actividad }}</td>
                                                    <td class="px-2 py-0.5">@{{ a.observacion }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </td>
                        </tr>

                    </template>


                    <tr v-if="rows.length === 0">
                        <td colspan="5" class="text-center py-6 text-zinc-500">
                            Sin información
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


    </div>
@endsection



@push('scripts')
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
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
                    showAsesores: false,
                    asesoresDisponibles: [],
                    totalesPorAsesor: [],
                    totales: {
                        total_oportunidades: 0,
                        total_actividades: 0
                    },
                    errorExport: '',
                    filters: {
                        start: null,
                        end: null,
                        asesores: [],
                        sector: '',
                        estado: '',
                        resultado: '',
                        fuente: ''
                    },
                    estadosDisponibles: [],
                    resultadosDisponibles: [],
                    fuentesDisponibles: [],
                    page: 1,
                    perPage: 50,
                    lastPage: 1,
                    _searchTimer: null,
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
                },


                 // ¿Hay filtro por asesor?
                hayFiltroAsesor() {
                    return this.filters.asesores.length > 0;
                },


                // Mostrar resumen global
                mostrarGlobal() {
                    return !this.hayFiltroAsesor;
                },

                // Mostrar resumen por asesor
                mostrarResumenAsesor() {
                    return this.hayFiltroAsesor && this.totalesPorAsesor.length > 0;
                },

                oportunidadesAgrupadas() {
                    const map = {};

                    this.rows.forEach(r => {
                        if (!map[r.oportunidad]) {
                                map[r.oportunidad] = {
                                    oportunidad: r.oportunidad,
                                    fechaRegistro: r.fechaRegistro,
                                    fechaCierre: r.fechaCierre,
                                    cliente: r.cliente,
                                    asesor: r.asesor,

                                    estado: r.estado,
                                    etapa: r.etapa,
                                    fuente: r.fuente,
                                    resultado: r.resultado,
                                    causa: r.causa,

                                    interes: r.interes,
                                    presupuesto: r.presupuesto,

                                    sector: r.sector,
                                    ciudad: r.ciudad,
                                    gremio: r.gremio,

                                    proveedorActual: r.proveedorActual,
                                    proveedorCompetidor: r.proveedorCompetidor,

                                    contacto: r.contacto,
                                    telefono: r.telefono,
                                    celular: r.celular,
                                    email: r.email,
                                    direccion: r.direccion,
                                    actividades: []
                                };
                        }

                        map[r.oportunidad].actividades.push({
                            actividad: r.actividad,
                            fecha: r.fechaActividad,
                            observacion: r.observacion
                        });
                    });

                    // 🔴 AQUÍ ESTÁ LA CLAVE
                    return Object.values(map)
                        .map(op => {
                            // ordenar actividades internas (más reciente primero)
                            op.actividades.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
                            // guardar fecha de última actividad
                            op._ultimaFecha = op.actividades[0]?.fecha ?? op.fechaRegistro;
                            return op;
                        })
                        .sort((a, b) => new Date(b._ultimaFecha) - new Date(a._ultimaFecha));
                }
            },


            watch: {
                search(newVal, oldVal) {
                    this.page = 1;
                    this.debounceBuscar();
                },
                'filters.start'() {
                    this.page = 1;
                    this.cargarDatos();
                },
                'filters.end'() {
                    this.page = 1;
                    this.cargarDatos();
                },
                'filters.asesores'() {
                    this.page = 1;
                    this.cargarDatos();
                },

                'filters.sector'() { this.page = 1; this.cargarDatos(); },
                'filters.estado'() { this.page = 1; this.cargarDatos(); },
                'filters.resultado'() { this.page = 1; this.cargarDatos(); },
                'filters.fuente'() { this.page = 1; this.cargarDatos(); },
            },


            methods: {

                debounceBuscar() {
                    clearTimeout(this._searchTimer);

                    this._searchTimer = setTimeout(() => {
                        this.cargarDatos();
                    }, 400); // 400ms es un debounce profesional
                },

                toggle(id) {
                    if (this.expanded === id) {
                        this.expanded = null;
                        return;
                    }

                    this.expanded = id;

                    this.$nextTick(() => {
                        const el = document.querySelector(`[data-op="${id}"]`);
                        if (el) {
                            el.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
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

                    if (this.filters.asesores.length) {
                        this.filters.asesores.forEach(a => {
                            params.append('asesores[]', a);
                        });
                    }

                    if (this.search) {
                        params.append('search', this.search);
                    }


                    if (this.filters.sector) {
                        params.append('sector', this.filters.sector);
                    }

                    if (this.filters.estado) {
                        params.append('estado', this.filters.estado);
                    }

                    if (this.filters.resultado) {
                        params.append('resultado', this.filters.resultado);
                    }

                    if (this.filters.fuente) {
                        params.append('fuente', this.filters.fuente);
                    }


                    const res = await fetch(
                        `{{ route('portal-crm.seguimiento.data') }}?${params.toString()}`
                    );

                    const json = await res.json();

                    if (json.success) {
                        this.rows = json.data;
                        this.totales = json.totales;
                        this.totalesPorAsesor = json.totales_por_asesor;
                        this.lastPage = json.pagination.last_page;
                    }
                },

                async cargarAsesores() {
                    const res = await fetch("{{ route('portal-crm.oportunidades.asesores') }}");
                    this.asesoresDisponibles = await res.json();
                },

                async cargarEstados() {
                    const res = await fetch("{{ route('portal-crm.estados.asesores') }}");
                    this.estadosDisponibles = await res.json();
                },

                // Exportar a Excel
                async exportarExcel() {

                    this.errorExport = '';

                    // 🔴 Validación VISUAL
                    if (!this.filters.start || !this.filters.end) {
                        this.errorExport = 'Debe seleccionar fecha inicio y fecha fin para exportar.';
                        return;
                    }


                    const params = new URLSearchParams();

                    if (this.filters.start) params.append('start', this.filters.start);
                    if (this.filters.end) params.append('end', this.filters.end);
                    if (this.filters.sector) params.append('sector', this.filters.sector);

                    this.filters.asesores.forEach(a => {
                        params.append('asesores[]', a);
                    });

                    window.location.href =
                        `{{ route('portal-crm.oportunidades.export') }}?${params.toString()}`;
                },
            },

            mounted() {
                this.cargarDatos();
                this.cargarAsesores();
                this.cargarEstados();
            }
        }).mount('#crmApp');
    </script>
@endpush
