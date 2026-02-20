@extends('layouts.app')

@section('content')
    <style>
        .excel-drop {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            background: #f8fafc;
        }

        [v-cloak] {
            display: none;
        }
    </style>

    <div id="app" v-cloak class="space-y-6">

        {{-- HEADER --}}
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">
                Cargar solicitud de material
            </h2>

            <span class="text-xs text-zinc-500">
                OT #{{ $ordenTrabajo->n_documento }}
            </span>
        </div>

        {{-- INFO BASE --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">

            <div class="border rounded p-3">
                <strong>Instalador</strong><br>
                {{ optional($ordenTrabajo->instalador)->nombre_instalador ?? '—' }}
            </div>

            <div class="border rounded p-3">
                <strong>Cliente</strong><br>
                {{ $ordenTrabajo->tercero }}
            </div>

            <div class="border rounded p-3">
                <strong>Estado OT</strong><br>
                <span class="px-2 py-0.5 rounded bg-zinc-100">
                    {{ $ordenTrabajo->status }}
                </span>
            </div>

        </div>



        @if (isset($solicitud) && $solicitud)
            <div class="border rounded-lg p-4 bg-green-50 text-sm">
                <div class="flex justify-between">
                    <div>
                        <strong>Compra registrada</strong><br>
                        Consecutivo: {{ $solicitud->consecutivo_compra }}<br>
                        Estado: {{ strtoupper($solicitud->status) }}
                    </div>
                    <div class="text-xs text-zinc-500">
                        {{ \Carbon\Carbon::parse($solicitud->fecha_registro)->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        @endif



        {{-- FORM --}}
        <form ref="form" action="{{ route('solicitudes.store', $ordenTrabajo->id_work_order) }}" method="POST"
            enctype="multipart/form-data" class="space-y-6" @submit.prevent="submitForm">

            @csrf

            {{-- IDs ocultos --}}
            <input type="hidden" name="orden_trabajo_id" value="{{ $ordenTrabajo->id_work_order }}">
            <input type="hidden" name="instalador_id" value="{{ $ordenTrabajo->instalador_id }}">


            <div>
                <label class="text-xs font-medium">
                    Buscar Orden de Compra (131)
                </label>

                <input type="text" v-model="searchCompra" @input="buscarCompra"
                    placeholder="Ingrese número de documento..." class="mt-1 w-full border rounded px-2 py-1 text-xs">

                <div v-if="resultados.length" class="border mt-2 rounded bg-white max-h-40 overflow-y-auto">


                    @if (!isset($solicitud))
                        {{-- buscador compra --}}
                    @else
                        <div class="text-xs text-zinc-500">
                            Esta orden ya tiene una solicitud registrada.
                        </div>
                    @endif

                    <div v-for="r in resultados" :key="r.IntDocumento" @click="seleccionarCompra(r)"
                        class="px-3 py-2 text-xs hover:bg-zinc-100 cursor-pointer">

                        <strong>@{{ r.IntDocumento }}</strong>
                        <div class="text-zinc-500">
                            @{{ r.proveedor }}
                        </div>

                    </div>

                </div>
            </div>


            {{-- OBSERVACIONES --}}
            <div>
                <label class="text-xs font-medium">Observaciones</label>
                <textarea name="observaciones" rows="3" class="mt-1 w-full border rounded px-2 py-1 text-xs"
                    placeholder="Observaciones generales de la solicitud…"></textarea>
            </div>





            {{-- BOTONES --}}
            <div class="flex justify-end gap-2 mt-3">

                <a href="{{ route('ordenes.trabajo.asignadas') }}" class="btn btn-outline-secondary btn-sm">
                    Cancelar
                </a>

                @if (isset($solicitud) && $solicitud->status === 'approved')
                    <button type="submit" disabled class="btn btn-success btn-sm">
                        Solicitud aprobada
                    </button>
                @else
                    <button type="submit" :disabled="enviando" class="btn btn-dark btn-sm">
                        Guardar solicitud
                    </button>
                @endif

            </div>

        </form>



        {{-- BOTÓN APROBAR FUERA DEL FORM PRINCIPAL --}}
        @if (isset($solicitud) && $solicitud->status !== 'approved')
            <form method="POST" action="{{ route('solicitudes.approve', $solicitud->id_solicitud_material) }}"
                class="d-inline">
                @csrf
                <button type="submit" onclick="return confirm('¿Desea aprobar esta solicitud?')"
                    class="btn btn-success btn-sm">
                    Aprobar solicitud
                </button>
            </form>
        @endif

    </div>
@endsection



@push('scripts')
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const {
                createApp
            } = Vue;

            createApp({
                data() {
                    return {
                        searchCompra: '',
                        resultados: [],
                        enviando: false,
                        error: null
                    }
                },

                methods: {

                    async buscarCompra() {

                        if (!this.searchCompra || this.searchCompra.length < 3) {
                            this.resultados = [];
                            return;
                        }

                        try {
                            const resp = await fetch(
                                `/compras-131/buscar?search=${this.searchCompra}`
                            );

                            if (!resp.ok) {
                                this.resultados = [];
                                return;
                            }

                            this.resultados = await resp.json();

                        } catch (e) {

                            this.resultados = [];
                        }
                    },

                    async seleccionarCompra(compra) {

                        if (!confirm(`¿Importar compra ${compra.IntDocumento}?`)) return;

                        this.enviando = true;

                        try {

                            const resp = await fetch(
                                `/solicitudes/importar-compra`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        orden_id: {{ $ordenTrabajo->id_work_order }},
                                        documento: compra.IntDocumento
                                    })
                                }
                            );

                            const json = await resp.json();

                            if (!resp.ok || !json.success) {
                                alert(json.message || 'Error al importar compra.');
                                this.enviando = false;
                                return;
                            }


                            this.resultados = [];
                            this.searchCompra = '';
                            alert('Compra registrada correctamente.');
                            window.location.reload();

                        } catch (e) {

                            alert('Error de comunicación.');
                        }

                        this.enviando = false;
                    }

                }
            }).mount('#app');

        });
    </script>
@endpush
