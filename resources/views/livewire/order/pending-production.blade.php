<div>
    {{-- Stop trying to control. --}}
    <div class="space-y-4">
        {{-- Header --}}

        {{-- Filtros colapsables y compactos (no se re-renderizan con el poll) --}}
        <details class="rounded border border-zinc-200 bg-zinc-50 p-2 pb-3" open>
            <summary class="cursor-pointer text-[11px] text-zinc-700 select-none leading-none">
                Filtros
            </summary>

            <div class="mt-1.5 flex flex-wrap items-center gap-1">
                {{-- Buscar global --}}
                <input type="text" wire:model.live.debounce.300ms="prodQ"
                    placeholder="Buscar: ticket/pedido/producto/asesor"
                    class="h-8 w-[26rem] max-w-full rounded border border-zinc-300 px-2 text-[11px]
                  focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />

                {{-- Estado --}}
                <select wire:model.live="prodStatus"
                    class="h-8 w-[8.5rem] rounded border border-zinc-300 px-2 text-[11px] bg-white
                   focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">Estado: todos</option>
                    <option value="queued">En cola</option>
                    <option value="in_progress">En producción</option>
                    <option value="done">Terminado para entrega</option>
                    <!--<option value="approved">Aprobado</option>-->
                </select>

                {{-- Pedido / Asesor / Producto: campos cortos --}}
                <input type="text" wire:model.defer="prodPedido" placeholder="Pedido"
                    class="h-8 w-24 rounded border border-zinc-300 px-2 text-[11px]
                  focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                <input type="text" wire:model.defer="prodAsesor" placeholder="Asesor"
                    class="h-8 w-28 rounded border border-zinc-300 px-2 text-[11px]
                  focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                <input type="text" wire:model.defer="prodProducto" placeholder="Producto"
                    class="h-8 w-32 rounded border border-zinc-300 px-2 text-[11px]
                  focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />

                {{-- Rango fecha compacto --}}
                <input type="date" wire:model.defer="prodStart"
                    class="h-8 w-36 rounded border border-zinc-300 px-2 text-[11px]
                  focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                <input type="date" wire:model.defer="prodEnd"
                    class="h-8 w-36 rounded border border-zinc-300 px-2 text-[11px]
                  focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />

                {{-- Controles rápidos --}}
                <select wire:model.live="prodPerPage"
                    class="h-8 w-[5.5rem] rounded border border-zinc-300 px-2 text-[11px] bg-white
                   focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option>10</option>
                    <option>15</option>
                    <option>20</option>
                    <option>30</option>
                </select>

                <button wire:click="reloadProduction" wire:loading.attr="disabled"
                    class="h-8 px-2 rounded border bg-zinc-200 text-[11px] hover:bg-zinc-100">
                    Buscar
                </button>

                <button wire:click="clearProductionFilters"
                    class="h-8 px-2 rounded border border-zinc-300 text-[11px] hover:bg-zinc-100">
                    Limpiar
                </button>


            </div>

        </details>


        <div class="py-2"></div>

        @php
            $perfil = (int) (auth()->user()->perfil_usuario_id ?? 0);
            $isAdmin = in_array($perfil, [1, 2], true);
            $isAsesor = $perfil === 5;
        @endphp


        {{-- Tabla (auto-refresh solo aquí) --}}
        <div class="overflow-x-auto rounded-lg border border-zinc-200">
            <table class="w-full text-xs leading-tight ">
                <thead>
                    <tr class="bg-zinc-100 text-left">
                        <th class="px-2 py-1 font-medium">Ticket</th>
                        <th class="px-2 py-1 font-medium">Pedido</th>
                        <th class="px-2 py-1 font-medium">Cliente</th>
                        <th class="px-2 py-1 font-medium">Asesor</th>
                        <th class="px-2 py-1">Estado</th>
                        <th class="px-2 py-1">Acciones</th>
                    </tr>
                </thead>

                {{-- 🔁 Auto-refresh cada 20s solo si la tabla es visible --}}
                <tbody @if ($isAsesor && !$showDetail) wire:poll.visible.20s="reloadProduction" @endif
                    class="[&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-zinc-50">
                    @forelse ($prodRowsPaginated as $i => $r)
                        <tr class="border-b border-zinc-200 hover:bg-zinc-50"
                            wire:key="row-{{ $r['Ndocumento'] ?? $i }}">

                            <td class="px-2 py-1 whitespace-nowrap font-mono">{{ $r['ticket_code'] }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $r['pedido'] }}</td>
                            <td class="px-2 py-1 max-w-56 truncate" title="{{ $r['tercero'] }}">{{ $r['tercero'] }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $r['vendedor'] }}</td>



                            @php
                                $status = $r['status'] ?? null;

                                $badgeClass = match ($status) {
                                    'queued' => 'danger', // gris
                                    'in_progress' => 'info', // celeste
                                    'done' => 'success', // azul
                                    // azul
                                    // azul
                                    //'approved' => 'success', // verde
                                    default => 'secondary',
                                };

                                $label = match ($status) {
                                    'queued' => 'En cola',
                                    'in_progress' => 'En producción',
                                    'done' => 'Terminado para entrega',
                                    //'approved' => 'Aprobado',
                                    default => '',
                                };
                            @endphp
                            <td>
                                @if ($label !== '')
                                    <button class=" btn-sm btn-{{ $badgeClass }}">{{ $label }}</button>
                                @endif
                            </td>
                            <td class="px-2 py-1 text-right">

                                <button
                                    wire:click="openDetailsByDoc({{ $r['n_documento'] }}, @js($r['n_documento']))"
                                    type="button" class="btn btn-dark btn-sm"
                                    data-ndocumento="{{ $r['n_documento'] }}">
                                    Pedido
                                </button>

                                <select class="h-8 rounded border border-zinc-300 px-2 text-[11px] bg-white"
                                    onchange="Livewire.dispatch('production.open', { docId: {{ $r['id_production_order'] }}, action: this.value = '' })">
                                    <option value="">Acción…</option>

                                    @if ($isAsesor)
                                        {{-- Solo lectura para Asesor, sin importar el estado --}}
                                        <option value="programar">Ver</option>
                                    @else
                                        @switch($r['status'] ?? '')
                                            @case('queued')
                                                <option value="empezar">Empezar</option>
                                            @break

                                            @case('in_progress')
                                                <option value="programar">Ver</option>
                                                <option value="finalizar">Finalizar</option>
                                            @break

                                            @case('done')
                                                <option value="done">Ver</option>
                                            @break

                                            @default
                                                <option value="programar">No acción</option>
                                        @endswitch
                                    @endif
                                </select>

                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-2 py-6 text-center text-zinc-500">Sin resultados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="pt-2 text-xs">
                {{ $prodRowsPaginated->onEachSide(1)->links() }}
            </div>

            <livewire:order.order-detail-modal />
            <livewire:order.production-action-modal />


            <div wire:ignore.self class="modal fade" id="ensambleModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Ficha de Ensamble / Insumos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            @php $h = $detail['header'] ?? []; @endphp

                            <div class="mb-3 grid grid-cols-2 gap-4">
                                <div>
                                    <strong>Ficha (141):</strong> {{ $h['FichaDocumento'] ?? '-' }} <br>
                                    <strong>Fecha ficha:</strong> {{ $h['FechaFicha'] ?? '-' }} <br>
                                    <strong>Pedido asociado (ficha):</strong> {{ $h['PedidoAsociadoFicha'] ?? '-' }} <br>
                                    <strong>Cliente (ficha):</strong> {{ $h['ClienteFicha'] ?? '-' }} <br>
                                    <strong>Asesor (ficha):</strong> {{ $h['VendedorFicha'] ?? '-' }} <br>
                                </div>

                                <div>
                                    <strong>Orden producción (ensamble, 140):</strong> {{ $h['EnsambleDocumento'] ?? '-' }}
                                </div>
                            </div>

                            <hr>

                            <h6 class="mb-2">Detalle de la ficha (insumos)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-end">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($detail['lines']))
                                            @foreach ($detail['lines'] as $line)
                                                <tr>
                                                    <td>{{ $line['Luminaria'] }}</td>
                                                    <td class="text-end">{{ number_format($line['Cantidad'],2) }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="2" class="text-center text-muted">Sin insumos registrados.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>


            @once
                @push('scripts')
                    <script>
                        (function() {
                            // modalInstance queda en el closure y NO en el scope global
                            let modalInstance;

                            window.addEventListener('open-order-detail-ficha', () => {
                                const el = document.getElementById('ensambleModal');
                                if (!el) return;

                                if (!modalInstance) {
                                    modalInstance = new bootstrap.Modal(el, {
                                        backdrop: 'static',
                                        keyboard: false
                                    });
                                }
                                modalInstance.show();
                            });

                            window.addEventListener('ui:hide-production-modal', () => {
                                const el = $('#ensambleModal');
    if (el.length) {
        el.modal('hide');
    }
                            });
                        })
                        ();
                    </script>
                @endpush
            @endonce




        </div>




    </div>
