@extends('layouts.app')

@section('content')
    <style>
        [v-cloak] {
            display: none;
        }


        .fade-scale-enter-active,
        .fade-scale-leave-active {
            transition: all 0.25s ease;
        }

        .fade-scale-enter-from,
        .fade-scale-leave-to {
            opacity: 0;
            transform: scale(0.95);
        }

        /* Skeleton */
        .skeleton {
            background: linear-gradient(90deg,
                    #eee 25%,
                    #f5f5f5 37%,
                    #eee 63%);
            background-size: 400% 100%;
            animation: shimmer 1.4s ease infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -400px 0;
            }

            100% {
                background-position: 400px 0;
            }
        }


        /* ===== MODAL FOTOS ===== */

        .thumb {
            width: 100%;
            padding-top: 100%;
            position: relative;
            overflow: hidden;
            border-radius: 6px;
            background: #000;
            cursor: pointer;
        }

        .thumb img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Preview */
        .preview-wrapper {
            position: relative;
            height: 70vh;
            max-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .preview-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Navegación */
        .nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3rem;
            color: #fff;
            background: rgba(0, 0, 0, .4);
            border-radius: 50%;
            width: 48px;
            height: 48px;
            line-height: 44px;
            text-align: center;
            cursor: pointer;
            z-index: 10;
        }

        .nav.prev {
            left: 15px;
        }

        .nav.next {
            right: 15px;
        }

        .btn-back {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }
    </style>

    <div id="crmEventosApp" v-cloak class="space-y-2">

        <h2 class="text-lg font-semibold">Visitas / Eventos</h2>

        <transition name="fade">
            <div v-if="mostrarGlobal" class="grid grid-cols-2 md:grid-cols-4 gap-3 my-3">

                <div class="bg-white border rounded p-3">
                    <div class="text-xs text-zinc-500">Eventos</div>
                    <div class="text-xl font-semibold text-indigo-600">
                        @{{ totales.total_eventos }}
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

        {{-- Filtros --}}
        <div class="flex flex-wrap gap-1 items-end bg-zinc-50 p-2 rounded border">

            <div>
                <label class="text-xs">Desde</label>
                <input type="date" v-model="filters.start" class="h-8 border rounded px-2 text-xs">
            </div>

            <div>
                <label class="text-xs">Hasta</label>
                <input type="date" v-model="filters.end" class="h-8 border rounded px-2 text-xs">
            </div>


            <div>
                <label class="text-xs">Tipo Evento</label>
                <select v-model="filters.tipoEvento" class="h-8 border rounded px-2 text-xs">
                    <option value="">Todos</option>
                    <option value="10">Comercial</option>
                    <option value="11">Servicio al cliente</option>
                    <option value="21">Visita Cliente</option>
                    <option value="22">Cotización Relevante</option>
                    <option value="23">Productos Sugeridos</option>
                </select>
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

            <button @click="cargarDatos" class="h-8 px-4 bg-black text-white text-xs rounded">
                Consultar
            </button>

            <button @click="exportarExcel" title="Exportar datos a excel"
                class="h-8 px-4 bg-green-600 text-white text-xs rounded"> 📥 Exportar Excel
            </button>

            <input type="text" v-model="search" placeholder="Buscar cliente, actividad u observación"
                class="h-8 flex-1 border rounded px-2 text-xs">
        </div>


        @if (session('error'))
            <div id="alert-error"
                class="relative bg-red-100 border border-red-500 text-red-800 px-4 py-2 rounded mb-3 text-sm flex items-start gap-2">
                <span class="text-red-600 text-lg">⚠️</span>

                <div class="flex-1">
                    {{ session('error') }}
                </div>

                <button onclick="document.getElementById('alert-error').remove()"
                    class="text-red-600 hover:text-red-900 font-bold px-2" aria-label="Cerrar">
                    ✕
                </button>
            </div>
        @endif

        {{-- Tabla --}}
        <div class="overflow-x-auto border rounded">
            <table class="w-full text-xs">
                <thead class="bg-zinc-100">
                    <tr>
                        <th class="px-1 py-0.5">Fecha Registrada</th>
                        <th class="px-1 py-0.5">Cliente</th>
                        <th class="px-1 py-0.5">Asesor</th>
                        <th class="px-1 py-0.5">Tipo Evento</th>
                        <th class="px-1 py-0.5">Actividad</th>
                        <th>Archivos adjunto</th>
                    </tr>
                </thead>

                <tbody>
                    <template v-for="evento in eventosAgrupados" :key="evento.IntIdEvento">


                        {{-- FILA PRINCIPAL --}}
                        <tr class="border-b hover:bg-zinc-50">
                            <td class="px-1 py-0.5">@{{ evento.FechaRegistroDocumento }}</td>

                            <td>
                                <button class="mr-1 text-blue-600 font-bold" @click="toggle(evento.IntIdEvento)">
                                    @{{ expanded === evento.IntIdEvento ? '−' : '+' }}
                                </button>
                                @{{ evento.NombreTercero }}
                            </td>

                            <td>@{{ evento.NombreAsesor }}</td>
                            <td>@{{ evento.TipoEvento }}</td>

                            <td>
                                <span
                                    class="inline-flex items-center rounded-full
                                    bg-indigo-100 text-indigo-700
                                    px-2 py-0.5 text-[11px] font-medium">
                                    @{{ evento.actividades.length }} actividades
                                </span>
                            </td>

                            <td>
                                <button
                                    v-if="evento.cantidad_adjuntos > 0"
                                    class="mt-2 inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline"
                                    @click="verFotos(evento.IntIdEvento)">
                                    📷 Ver archivos
                                </button>

                                <span v-else class="text-xs text-zinc-400">
                                    Sin adjuntos
                                </span>

                            </td>

                        </tr>
                        {{-- FILA EXPANDIDA --}}
                        <tr v-if="expanded === evento.IntIdEvento" class="bg-zinc-50 border-b">
                            <td colspan="5" class="px-3 py-2">
                                <div class="bg-white border rounded-md p-2 text-[11px] shadow-sm space-y-2">

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-1">
                                        <div><strong>NIT:</strong> @{{ evento.NitTercero }}</div>
                                        <div><strong>Referencia:</strong> @{{ evento.ReferenciaCliente }}</div>
                                        <div><strong>Evento:</strong> @{{ evento.TipoEvento }}</div>
                                        <div class="mb-1">
                                            <strong>Detalle de la visita:</strong>
                                            <textarea name="observaciones" id="observaciones_@{{ evento.IntIdEvento }}" cols="30" rows="10">@{{ evento.Observaciones }}</textarea>
                                        </div>
                                    </div>


                                    <div class="mt-4">
                                        <strong>Actividades realizadas</strong>

                                        <table class="w-full text-[11px] mt-1">
                                            <thead class="bg-zinc-100">
                                                <tr>
                                                    <th class="px-1 py-0.5">Fecha Registrada</th>
                                                    <th class="px-1 py-0.5">Actividad</th>
                                                    <th class="px-1 py-0.5">Detalle</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(act, i) in evento.actividades" :key="i">
                                                    <td>@{{ act.FechaActividad }}</td>
                                                    <td>@{{ act.TipoActividad }}</td>
                                                    <td>@{{ act.DetalleActividad }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
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

            {{-- Paginación --}}
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





        <div class="modal fade" id="modalFotos" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" role="document">
                <div class="modal-content">

                    <!-- HEADER -->
                    <div class="modal-header">
                        <h5 class="modal-title">
                            📷 Fotos evento #@{{ eventoFotosId }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body bg-white p-0">

                        <!-- GRID -->

                        <div v-if="previewIndex === null"
                            class="p-3"
                            style="max-height:70vh; overflow-y:auto;">
                            <div class="row">
                                <div class="col-4 mb-3"
                                    v-for="(f, index) in fotosEvento"
                                    :key="f.IdSeguridad">

                                    <!-- IMAGEN -->
                                    <div v-if="f.extension !== 'pdf'"
                                        class="thumb"
                                        @click="abrirPreview(index)">
                                        <img :src="`{{ route('crm.imagen') }}?id=${f.IdSeguridad}`">
                                    </div>

                                    <!-- PDF -->
                                    <div v-else
                                        class="d-flex flex-column align-items-center justify-content-center text-muted">
                                        <div style="font-size:4rem">📄</div>
                                        <button class="btn btn-outline-primary btn-sm mt-2"
                                                @click="abrirPdf(f)">
                                            Abrir PDF
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- PREVIEW -->
                        <div v-else class="d-flex align-items-center justify-content-center"
                            style="height:70vh; position:relative;">

                            <button class="btn btn-light btn-sm position-absolute" style="top:10px; left:10px"
                                @click="volverAGrid">
                                ← Volver
                            </button>

                            <button v-if="previewIndex > 0" class="position-absolute text-white"
                                style="left:15px; font-size:3rem" @click="anterior">
                                ‹
                            </button>

                            <img v-if="esImagen(fotosEvento[previewIndex])"
                            :src="`{{ route('crm.imagen') }}?id=${fotosEvento[previewIndex].IdSeguridad}`"
                            style="max-width:95%; max-height:95%; object-fit:contain;">

                            <button v-if="previewIndex < fotosEvento.length - 1" class="position-absolute text-white"
                                style="right:15px; font-size:3rem" @click="siguiente">
                                ›
                            </button>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cerrar
                        </button>
                    </div>

                </div>
            </div>
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
                    totales: {
                        total_eventos: 0,
                        total_actividades: 0
                    },
                    previewIndex: null,
                    modalFotos: false,
                    showAsesores: false,
                    asesoresDisponibles: [],
                    totalesPorAsesor: [],
                    fotosEvento: [],
                    eventoFotosId: null,
                    cargandoFotos: false,
                    search: '',
                    expanded: null,
                    filters: {
                        start: '',
                        end: '',
                        asesores: [],
                        tipoEvento: '',
                    },
                    page: 1,
                    perPage: 50,
                    lastPage: 1,
                    _searchTimer: null,
                }
            },

            watch: {
                // Búsqueda
                search() {
                    this.page = 1;
                    this.debounceBuscar();
                },
                'filters.start'() {
                    this.page = 1;
                    if (this.filters.start && this.filters.end) {
                        this.cargarDatos();
                    }
                },
                'filters.end'() {
                    this.page = 1;
                    if (this.filters.start && this.filters.end) {
                        this.cargarDatos();
                    }
                },

                'filters.tipoEvento'() {
                    this.page = 1;
                    this.cargarDatos();
                }

            },

            methods: {
                // Búsqueda con debounce
                debounceBuscar() {
                    clearTimeout(this._searchTimer);
                    this._searchTimer = setTimeout(() => {
                        this.cargarDatos();
                    }, 400);
                },

                // Expandir / contraer fila
                toggle(id) {
                    this.expanded = this.expanded === id ? null : id;
                },


                // Cargar datos
                async cargarDatos() {
                    const params = new URLSearchParams({
                        page: this.page,
                        per_page: this.perPage
                    });

                    if (this.filters.start) params.append('start', this.filters.start);
                    if (this.filters.end) params.append('end', this.filters.end);
                    if (this.search) params.append('search', this.search);
                    if (this.filters.tipoEvento) {
                        params.append('tipoEvento', this.filters.tipoEvento);
                    }

                    // 🔥 ASESORES (ARRAY)
                    if (this.filters.asesores.length) {
                        this.filters.asesores.forEach(a => {
                            params.append('asesores[]', a);
                        });
                    }

                    const res = await fetch(
                        `{{ route('portal-crm.eventos.data') }}?${params.toString()}`
                    );

                    const json = await res.json();

                    if (json.success) {
                        this.rows = json.data;
                        this.totales = json.totales;
                        this.totalesPorAsesor = json.totales_por_asesor;
                        this.lastPage = json.pagination.last_page;
                    }
                },

                // Cargar lista de asesores
                async cargarAsesores() {
                    const res = await fetch("{{ route('portal-crm.eventos.asesores') }}");
                    this.asesoresDisponibles = await res.json();
                },

                // Exportar a Excel
                async exportarExcel() {
                    const params = new URLSearchParams();

                    if (this.filters.start) params.append('start', this.filters.start);
                    if (this.filters.end) params.append('end', this.filters.end);
                    if (this.filters.tipoEvento) params.append('tipoEvento', this.filters.tipoEvento);

                    this.filters.asesores.forEach(a => {
                        params.append('asesores[]', a);
                    });

                    window.location.href =
                        `{{ route('portal-crm.eventos.export') }}?${params.toString()}`;
                },

                // Mostrar fotos adjuntas
                async verFotos(idEvento) {
                    // this.modalFotos = true;
                    this.previewIndex = null;
                    this.eventoFotosId = idEvento;
                    this.cargandoFotos = true;
                    this.fotosEvento = [];

                    $('#modalFotos').modal('show');

                    await new Promise(r => setTimeout(r, 300)); // suaviza UX

                    const res = await fetch(
                        `{{ route('portal-crm.eventos.fotos', ['evento' => '___ID___']) }}`
                            .replace('___ID___', idEvento)
                    );

                    this.fotosEvento = await res.json();

                    this.cargandoFotos = false;
                },


                abrirPreview(index) {
                    this.previewIndex = index;
                },

                abrirPdf(file) {
                    window.open(
                        `{{ route('crm.imagen') }}?id=${file.IdSeguridad}`,
                        '_blank'
                    );
                },

                volverAGrid() {
                    this.previewIndex = null;
                },

                cerrarModal() {
                    $('#modalFotos').modal('hide');
                    this.previewIndex = null;
                },

                siguiente() {
                    if (this.previewIndex < this.fotosEvento.length - 1) {
                        this.previewIndex++;
                    }
                },

                anterior() {
                    if (this.previewIndex > 0) {
                        this.previewIndex--;
                    }
                },

                esImagen(file) {
                    const ext = file.extension?.toLowerCase();
                    return ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
                }


            },

            computed: {

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

                // Eventos agrupados
                eventosAgrupados() {
                    const map = {};

                    this.rows.forEach(r => {
                        if (!map[r.IntIdEvento]) {
                            map[r.IntIdEvento] = {
                                IntIdEvento: r.IntIdEvento,
                                FechaRegistroDocumento: r.FechaRegistroDocumento,
                                NombreTercero: r.NombreTercero,
                                NombreAsesor: r.NombreAsesor,
                                TipoEvento: r.TipoEvento,
                                Observaciones: r.Observaciones,
                                NitTercero: r.NitTercero,
                                ReferenciaCliente: r.ReferenciaCliente,
                                cantidad_adjuntos: r.cantidad_adjuntos ?? 0,
                                actividades: []
                            };
                        }

                        map[r.IntIdEvento].actividades.push({
                            TipoActividad: r.TipoActividad,
                            FechaActividad: r.FechaRegistro,
                            DetalleActividad: r.DetalleActividad
                        });
                    });

                    return Object.values(map);
                }
            },

            mounted() {
                this.cargarDatos();
                this.cargarAsesores();
            }
        }).mount('#crmEventosApp');
    </script>
@endpush
