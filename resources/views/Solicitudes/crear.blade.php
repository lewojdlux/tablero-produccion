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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- COLUMNA IZQUIERDA - MATERIALES --}}
            @if ($pedidoMaterialItem->count())
                <div class="border rounded-lg p-4 bg-white shadow-sm">
                    <h3 class="text-sm font-semibold mb-3">
                        Materiales solicitados
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs border">
                            <thead class="bg-zinc-100">
                                <tr>
                                    <th class="px-3 py-2 text-left border">Código</th>
                                    <th class="px-3 py-2 text-left border">Descripción</th>
                                    <th class="px-3 py-2 text-center border">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pedidoMaterialItem as $item)
                                    <tr class="hover:bg-zinc-50">
                                        <td class="px-3 py-2 border">
                                            {{ $item->codigo_material }}
                                        </td>
                                        <td class="px-3 py-2 border">
                                            {{ $item->descripcion_material }}
                                        </td>
                                        <td class="px-3 py-2 text-center border font-semibold">
                                            {{ $item->cantidad }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif


            {{-- COLUMNA DERECHA - COMPRA --}}
            @if ($solicitudes->count())

                @foreach ($solicitudes as $solicitud)

                    <div class="border rounded-lg p-4 bg-white shadow-sm h-fit mb-3">
                        <h3 class="text-sm font-semibold mb-3">
                            Compra registrada
                        </h3>

                        <div class="text-sm space-y-2">
                            <div>
                                <span class="text-zinc-500">Consecutivo:</span><br>
                                <span class="font-semibold">
                                    {{ $solicitud->consecutivo_compra }}
                                </span>
                            </div>

                            <div>
                                <span class="text-zinc-500">Estado:</span><br>
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    {{ $solicitud->status === 'approved' 
                                        ? 'bg-green-100 text-green-700' 
                                        : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ strtoupper($solicitud->status) }}
                                </span>
                            </div>

                            <div class="text-xs text-zinc-500 pt-2 border-t">
                                {{ \Carbon\Carbon::parse($solicitud->fecha_registro)->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>

                @endforeach

            @endif

        </div>


        @if (!$solicitudes->where('status', 'approved')->count())
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

            </form>
        @endif

        {{-- ================== BOTONES INFERIORES ================== --}}

        <div class="flex justify-between items-center mt-6 border-t pt-4">

            {{-- BOTÓN VOLVER (SIEMPRE VISIBLE) --}}
            <a href="{{ route('ordenes.trabajo.asignadas') }}" class="btn btn-outline-secondary btn-sm">
                Volver
            </a>

            <div class="flex gap-2">

                {{-- BOTÓN GUARDAR --}}
                @if (!isset($solicitud) || $solicitud->status !== 'approved')
                    <button type="button" @click="$refs.form.submit()" :disabled="enviando" class="btn btn-dark btn-sm">
                        Guardar solicitud
                    </button>
                @endif

                {{-- BOTÓN APROBAR --}}
                @foreach ($solicitudes->where('status','!=','approved') as $solicitud)
                    <form method="POST" action="{{ route('solicitudes.approve', $solicitud->id_solicitud_material) }}">
                        @csrf
                        <button type="submit"
                            onclick="return confirm('¿Desea aprobar esta solicitud?')"
                            class="btn btn-success btn-sm">
                            Aprobar solicitud {{ $solicitud->consecutivo_compra }}
                        </button>
                    </form>
                @endforeach

            </div>

        </div>


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
