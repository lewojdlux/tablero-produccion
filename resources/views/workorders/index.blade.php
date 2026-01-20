@extends('layouts.app')

@section('content')
    <div id="app" class="space-y-4">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Orden de trabajo</h2>



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
            $isInstalador = $perfil === 6;
            $isAsesor = $perfil === 5;
        @endphp

        {{-- Tabla --}}
        <div v-show="!mostrarForm" class="overflow-x-auto rounded-lg border border-zinc-200">
            <table class="w-full text-xs leading-tight">
                <thead>
                    <th class="px-2 py-1 font-medium">Consecutivo</th>
                    <th class="px-2 py-1 font-medium">Cliente</th>
                    <th class="px-2 py-1 font-medium">Asesor</th>
                    <th class="px-2 py-1 font-medium text-center">Acciones</th>
                </thead>

                <tbody class="[&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-zinc-50">
                    @forelse($dataMatrial as $workOrder)
                        <tr class="border-b border-zinc-200 hover:bg-zinc-50"
                            :style="shouldHide('{{ $workOrder->n_documento }}', '{{ $workOrder->vendedor }}',
                                '{{ $workOrder->tercero }}')">

                            <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->n_documento }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->tercero }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->vendedor }}</td>

                            <td class="px-2 py-1 text-center">
                                <button @click="openModal({{ $loop->index }})"
                                    class="px-3 py-1 bg-indigo-600 text-white rounded text-xs hover:bg-indigo-700">
                                    Asignar
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-2 py-6 text-center text-zinc-500">Sin resultados…</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div v-show="!mostrarForm" class="pt-2 text-xs">
            {{ $dataMatrial->links() }}
        </div>




        <!-- FORMULARIO DESPLEGABLE PARA ASIGNAR OT -->
        <div v-if="mostrarForm" class="mt-6 border rounded-lg p-4 bg-white shadow-md">

            <h3 class="text-lg font-semibold border-b pb-2 mb-4">Asignar Orden de Trabajo</h3>

            <div class="space-y-2 text-sm">
                <p><strong>Documento:</strong> @{{ form.n_documento }}</p>
                <p><strong>Cliente:</strong> @{{ form.tercero }}</p>
                <p><strong>Asesor:</strong> @{{ form.vendedor }}</p>
                <input type="hidden" v-model="form.facturado" class="mr-2">
                <input type="hidden" name="form.periodo" v-model="form.periodo">
                <input type="hidden" name="form.ano" v-model="form.ano">
                <input type="hidden" name="form.status" v-model="form.status">
                <input type="hidden" name="form.n_factura" v-model="form.n_factura">
                <input type="hidden" name="form.estado" v-model="form.estado">


                <label class="text-xs font-semibold">Instalador</label>
                <select v-model="form.instalador_id" class="w-full border rounded px-2 py-1 text-xs">
                    <option value="">Seleccione…</option>

                    @foreach ($instaladores as $inst)
                        <option value="{{ $inst->id_instalador }}">
                            {{ $inst->nombre_instalador }}
                        </option>
                    @endforeach
                </select>

                <label class="text-xs font-semibold">Observación</label>
                <textarea v-model="form.obsv_pedido" class="w-full border rounded px-2 py-1 text-xs" rows="2"></textarea>
            </div>




            <div class="flex justify-end gap-2 mt-6 ">
                <button @click="cancelarForm" class="px-4 py-2 rounded border hover:bg-gray-100 text-xs">
                    Cancelar
                </button>

                <button @click="registrarOT" class="px-4 py-2 rounded border bg-white text-dark hover:bg-gray-800 text-xs">
                    Registrar OT
                </button>
            </div>

        </div>


    </div>
@endsection




@push('scripts')
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script>
        window.WORKORDERS = @json($dataMatrial->items());

        document.addEventListener("DOMContentLoaded", function() {

            const {
                createApp
            } = Vue;

            createApp({
                data() {
                    return {
                        mostrarForm: false,

                        modal: false,
                        search: "",
                        form: {
                            n_documento: "",
                            tercero: "",
                            vendedor: "",
                            vendedor_username: "",
                            periodo: "",
                            ano: "",
                            obsv_pedido: "",
                            n_factura: "",
                            instalador_id: "",
                            status: "pending",
                            estado: '',
                            description: ""
                        }
                    };
                },

                methods: {
                    // Mostrar/ocultar fila según búsqueda
                    shouldHide(doc, vendedor, cliente) {
                        const s = this.search.toLowerCase();
                        if (!s) return "";
                        const t = `${doc} ${vendedor} ${cliente}`.toLowerCase();
                        return t.includes(s) ? "" : "display:none;";
                    },

                    // Abre modal con la info del documento
                    openModal(index) {
                        const workOrder = window.WORKORDERS[index];

                        this.form = {
                            n_documento: workOrder.n_documento,
                            tercero: workOrder.tercero,
                            vendedor: workOrder.vendedor,
                            vendedor_username: workOrder.vendedor_username ?? "",
                            periodo: workOrder.periodo ?? "",
                            ano: workOrder.ano ?? "",
                            obsv_pedido: workOrder.obsv_pedido ?? "",
                            n_factura: workOrder.n_factura ?? "",
                            instalador_id: "",
                            status: "pending",
                            estado: workOrder.estado ?? '',
                            description: workOrder.description ?? ""
                        };

                        this.mostrarForm = true;

                        this.$nextTick(() => {
                            window.scrollTo({
                                top: 0,
                                behavior: "smooth"
                            });
                        });
                    },

                    cancelarForm() {
                        this.mostrarForm = false;
                    },

                    // Registrar OT
                    async registrarOT() {

                        if (!this.form.instalador_id) {
                            alert("Seleccione un instalador.");
                            return;
                        }

                        const resp = await fetch("{{ route('ordenes.trabajo.store') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(this.form)
                        });



                        const json = await resp.json();
                        if (json.success) {
                            alert("Orden registrada correctamente.");
                            this.mostrarForm = false;
                            location.reload();
                        }
                    }

                }

            }).mount("#app");

        });
    </script>
@endpush
