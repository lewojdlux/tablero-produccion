@extends('layouts.app')

@section('content')
    <div id="app" class="space-y-4">

        {{-- HEADER --}}
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Consultar Orden de Producción</h2>
        </div>

        {{-- BUSCADOR --}}
        <div class="rounded-lg border p-4 bg-white shadow-sm">
            <label class="text-xs font-medium text-zinc-700">Número de documento</label>

            <div class="mt-1 flex gap-2">
                <input v-model="ndoc" type="text" placeholder="Ingrese el número de orden"
                    class="h-9 w-48 rounded border border-zinc-300 px-2 text-xs
                    focus:border-black focus:ring-1 focus:ring-black">

                <button @click="buscarDocumento"
                    class="px-4 py-2 bg-black text-dark text-xs rounded-md border border-zinc-300 shadow-sm
                    hover:bg-zinc-800 transition">
                    Buscar
                </button>

            </div>
        </div>

        {{-- TABLA ITEMS AGREGADOS --}}
        <div v-if="ordenes.length" class="rounded-lg border p-4 bg-white shadow-sm">
            <h3 class="font-medium text-sm mb-3">Órdenes agregadas</h3>

            <table class="w-full text-xs border">
                <thead class="bg-zinc-100">
                    <tr>
                        <th class="px-2 py-1">Documento</th>
                        <th class="px-2 py-1">Cliente</th>
                        <th class="px-2 py-1">Vendedor</th>
                        <th class="px-2 py-1">Instalador</th>
                        <th class="px-2 py-1">Acción</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(o, i) in ordenes" :key="i">
                        <td class="border px-2 py-1">@{{ o.n_documento }}</td>
                        <td class="border px-2 py-1">@{{ o.tercero }}</td>
                        <td class="border px-2 py-1">@{{ o.vendedor }}</td>

                        <td class="border px-2 py-1">
                            <select v-model="o.instalador_id"
                                class="h-7 w-full rounded border border-zinc-300 text-xs px-1">
                                <option value="">Seleccione…</option>
                                @foreach ($instaladores as $inst)
                                    <option value="{{ $inst->id_instalador }}">
                                        {{ $inst->nombre_instalador }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td class="border px-2 py-1 text-center">
                            <button @click="registrar(o)"
                                class="px-3 py-1 bg-indigo-600 text-white rounded text-xs hover:bg-indigo-700">
                                Registrar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


        <!-- MODAL -->
        <div v-if="modal"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4">

            <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl p-6 animate-fadeIn">

                <h3 class="text-xl font-semibold mb-4 border-b pb-2">Orden encontrada</h3>

                <div class="space-y-2 text-sm leading-5">

                    <p><strong>Pedido:</strong> @{{ header.Ndocumento }}</p>
                    <p><strong>Cliente:</strong> @{{ header.Tercero }}</p>
                    <p><strong>Vendedor:</strong> @{{ header.Vendedor }}</p>
                    <p><strong>Periodo:</strong> @{{ header.Periodo }} - @{{ header.Ano }}</p>
                    <p><strong>Fecha OP:</strong> @{{ header.FechaOrdenProduccion }}</p>

                    <p><strong>Observaciones:</strong> @{{ header.Observaciones }}</p>

                    <p>
                        <strong>Estado factura:</strong>
                        <span
                            :class="header.EstadoFactura === 'FACTURADO' ?
                                'text-green-600 font-semibold' :
                                'text-red-600 font-semibold'">
                            @{{ header.EstadoFactura }}
                        </span>
                    </p>

                    <p v-if="header.NumeroFactura">
                        <strong>N° Factura:</strong> @{{ header.NumeroFactura }}
                    </p>

                </div>

                <!-- BOTONES -->
                <div class="flex justify-end mt-6 gap-3">
                    <button @click="modal=false" class="px-4 py-2 rounded border text-sm hover:bg-gray-100">
                        Cerrar
                    </button>

                    <button @click="agregarOrden" class="px-4 py-2 bg-black text-white rounded text-sm hover:bg-gray-800">
                        Agregar a la Orden de Trabajo
                    </button>
                </div>

            </div>
        </div>


    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const {
                createApp
            } = Vue;

            createApp({
                data() {
                    return {
                        ndoc: "",
                        header: null,
                        lines: [],
                        modal: false,
                        ordenes: []
                    };
                },

                methods: {
                    async buscarDocumento() {
                        if (!this.ndoc) return alert("Ingrese un documento");

                        const url = "{{ url('ordenes/trabajo/consultar') }}?ndoc=" + this.ndoc;

                        const resp = await fetch(url, {
                            method: "GET",
                            headers: {
                                "Accept": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            credentials: "include"
                        });

                        const data = await resp.json();

                        this.header = data.header;
                        this.lines = data.lines;
                        this.modal = true;
                    },

                    agregarOrden() {
                        this.ordenes.push({
                            n_documento: this.header.Ndocumento,
                            pedido: this.header.Pedido,
                            tercero: this.header.Tercero,
                            vendedor: this.header.Vendedor,
                            vendedor_username: this.header.VendedorUsername,
                            instalador_id: "",
                            periodo: this.header.Periodo,
                            ano: this.header.Ano,
                            n_factura: this.header.NumeroFactura ?? null, // <-- AHORA SÍ CORRECTO
                            obsv_pedido: this.header.Observaciones,
                            status: "pending",
                            description: ""
                        });

                        this.modal = false;
                    },

                    async registrar(o) {
                        if (!o.instalador_id)
                            return alert("Seleccione un instalador");

                        await fetch(`/ordenes/trabajo/registrar`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(o)
                        });

                        alert("Orden registrada");
                    }
                }
            }).mount('#app');
        });
    </script>
@endpush
