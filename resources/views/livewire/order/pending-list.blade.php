<div>
    {{-- Stop trying to control. --}}
    <div class="space-y-4">


        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Órdenes pendientes ERP</h2>

            {{-- ⬇️ INSERTA ESTO (tabs) --}}
            <div class="inline-flex rounded-lg overflow-hidden border border-zinc-300 mr-2">
                <button wire:click="$set('activeTab','pendientes')"
                    class="px-3 py-2 text-sm {{ $activeTab === 'pendientes' ? 'bg-indigo-600 text-white' : 'bg-white text-zinc-700' }}">
                    Pendientes ERP
                </button>
                <button wire:click="$set('activeTab','produccion')"
                    class="px-3 py-2 text-sm {{ $activeTab === 'produccion' ? 'bg-indigo-600 text-white' : 'bg-white text-zinc-700' }}">
                    En Producción
                </button>
            </div>
            {{-- ⬆️ HASTA AQUÍ --}}


        </div>

        @if ($activeTab === 'pendientes')
            {{-- Filtros colapsables y compactos (no se re-renderizan con el poll) --}}
            <details class="rounded border border-zinc-200 bg-zinc-50 p-2 pb-3" open x-data
                @focusin="$wire.pausePendingPoll = true" @focusout="$wire.pausePendingPoll = false">
                <summary class="cursor-pointer text-[11px] text-zinc-700 select-none leading-none">
                    Filtros
                </summary>

                <div class="mt-1.5 flex flex-wrap items-center gap-1">
                    {{-- Rango fecha --}}
                    <input id="pend-start" name="pend-start" type="date" wire:model.live="start"
                        class="h-8 w-36 rounded border border-zinc-300 px-2 text-[11px]
                    focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    <input id="pend-end" name="pend-end" type="date" wire:model.live="end"
                        class="h-8 w-36 rounded border border-zinc-300 px-2 text-[11px]
                    focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />

                    {{-- Asesor / Cliente / Pedido --}}
                    <input id="pend-asesor" name="pend-asesor" type="text" placeholder="Asesor"
                        wire:model.live.debounce.300ms="asesor"
                        class="h-8 w-28 rounded border border-zinc-300 px-2 text-[11px]
                    focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    <input id="pend-cliente" name="pend-cliente" type="text" placeholder="Cliente"
                        wire:model.live.debounce.300ms="cliente"
                        class="h-8 w-36 rounded border border-zinc-300 px-2 text-[11px]
                    focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    <input id="pend-pedido" name="pend-pedido" type="text" placeholder="Pedido"
                        wire:model.live.debounce.300ms="pedido"
                        class="h-8 w-24 rounded border border-zinc-300 px-2 text-[11px]
                    focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />

                    {{-- Per page + acciones --}}
                    <select id="pend-perpage" name="pend-perpage" wire:model.live="perPage"
                        class="h-8 w-[5.5rem] rounded border border-zinc-300 px-2 text-[11px] bg-white
                    focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option>10</option>
                        <option>15</option>
                        <option>20</option>
                        <option>30</option>
                    </select>

                    <button wire:click="applyPendingFilters" wire:loading.attr="disabled"
                        class="h-8 px-2 rounded border bg-zinc-200 text-[11px] hover:bg-zinc-100">
                        Buscar
                    </button>

                    <button wire:click="clearPendingFilters"
                        class="h-8 px-2 rounded border border-zinc-300 text-[11px] hover:bg-zinc-100">
                        Limpiar
                    </button>
                </div>
            </details>
        @endif

        <div class="py-2"></div>

        {{-- Inicio TAB Pendientes (ERP) --}}
        @if ($activeTab === 'pendientes')
            {{-- Tabla (auto-refresh solo aquí) --}}
            <div class="overflow-x-auto rounded-lg border border-zinc-200">
                <table class="w-full text-xs leading-tight ">
                    <thead>
                        <tr class="bg-zinc-100 text-left">
                            <th class="px-2 py-1 font-medium">Fecha registro</th>
                            <th class="px-2 py-1 font-medium">Pedido</th>
                            <th class="px-2 py-1 font-medium">Cliente</th>
                            <th class="px-2 py-1 font-medium">Asesor</th>
                            <!--<th class="px-2 py-1 font-medium">Producto</th>-->
                            <th class="px-2 py-1"></th>
                        </tr>
                    </thead>

                    {{-- 🔁 Auto-refresh cada 20s solo si la tabla es visible --}}
                    <tbody @if (!$showDetail) wire:poll.visible.20s="autoRefreshPendientes" @endif
                        class="[&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-zinc-50">
                        @forelse ($rowsPaginated as $i => $r)
                            <tr class="border-b border-zinc-200 hover:bg-zinc-50"
                                wire:key="row-{{ $r['Ndocumento'] ?? $i }}">

                                <td class="px-2 py-1 whitespace-nowrap font-mono">
                                    {{ date('d-m-Y', strtotime($r['FechaOrdenProduccion'])) }}</td>
                                <td class="px-2 py-1 whitespace-nowrap">{{ $r['Pedido'] }}</td>
                                <td class="px-2 py-1 max-w-56 truncate" title="{{ $r['Tercero'] }}">{{ $r['Tercero'] }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap">{{ $r['Vendedor'] }}</td>
                                <!--<td class="px-2 py-1 max-w-48 truncate" title="">

                                    </td>-->

                                <td class="px-2 py-1 text-right">

                                    <button
                                        wire:click="openDetailsByDoc({{ $r['Ndocumento'] }}, @js($r['Ndocumento']))"
                                        type="button" class="btn btn-info btn-sm"
                                        data-ndocumento="{{ $r['Ndocumento'] }}">
                                        Ver más
                                    </button>

                                    <button type="button" class="btn btn-success btn-sm"
                                        wire:click="openObservationModal({{ $r['Ndocumento'] }}, '{{ $r['Ndocumento'] }}')">
                                        Enviar a producción
                                    </button>
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
                {{ $rowsPaginated->onEachSide(1)->links() }}
            </div>



            {{-- Modal Detalle --}}
            <livewire:order.order-detail-modal />

            {{-- Scripts para el modal --}}



        @endif

        {{-- Fin TAB Producción --}}



        {{-- Inicio TAB Producción --}}
        @if ($activeTab === 'produccion')
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
                        <option value="done">Terminado</option>
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

            <br><br>

            @php
                $perfil = (int) (auth()->user()->perfil_usuario_id ?? 0);
                $isAdmin = in_array($perfil, [1, 2], true);
                $isAsesor = $perfil === 5;
            @endphp

            <div class="overflow-x-auto rounded-lg border border-zinc-200">
                <table class="w-full text-xs leading-tight ">
                    <thead>
                        <tr class="bg-zinc-100 text-left">
                            <th class="px-2 py-1 font-medium">Ticket</th>
                            <th class="px-2 py-1 font-medium">Pedido</th>
                            <th class="px-2 py-1 font-medium">Producto</th>
                            <th class="px-2 py-1 font-medium">Asesor</th>
                            <th class="px-2 py-1 font-medium">Estado</th>
                            <th class="px-2 py-1 font-medium">Actualizado</th>
                        </tr>
                    </thead>

                    {{-- auto-refresh solo si se ve la tabla --}}
                    <tbody wire:poll.visible.20s="reloadProduction"
                        class="[&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-zinc-50">
                        @forelse ($prodRowsPaginated as $row)
                            <tr class="border-b border-zinc-200 hover:bg-zinc-50"
                                wire:key="prod-{{ $row['id_production_order'] }}">
                                <td class="px-2 py-1 whitespace-nowrap font-mono">{{ $row['ticket_code'] ?? '—' }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap">{{ $row['pedido'] ?? '—' }}</td>
                                <td class="px-2 py-1 max-w-56 truncate" title="{{ $row['luminaria'] ?? '' }}">
                                    {{ $row['luminaria'] ?? '—' }}</td>
                                <td class="px-2 py-1 whitespace-nowrap">{{ $row['vendedor'] ?? '—' }}</td>
                                <td class="px-2 py-1 whitespace-nowrap">
                                    @php
                                        $raw = $row['status'] ?? '';

                                        // Normaliza a snake_case
                                        $status = \Illuminate\Support\Str::of($raw)
                                            ->lower()
                                            ->replace([' ', '-'], '_')
                                            ->value();

                                        // Etiquetas y clases
                                        $label = match ($status) {
                                            'queued' => 'En cola',
                                            'in_progress' => 'En producción',
                                            'done' => 'Terminado para entrega',
                                            'approved' => 'Aprobado',
                                            default => 'Ver',
                                        };

                                        $btnClass =
                                            [
                                                'approved' => 'btn-success',
                                                'done' => 'btn-success',
                                                'in_progress' => 'btn-warning',
                                                'queued' => 'btn-danger',
                                            ][$status] ?? 'btn-secondary';

                                        // Acción sugerida para el modal según estado
                                        $action =
                                            [
                                                'queued' => 'programar', // abrir modal para programar
                                                'in_progress' => 'actualizar', // ver/actualizar avance
                                                'done' => 'entregar', // confirmar entrega
                                                'approved' => 'ver', // solo ver
                                            ][$status] ?? 'ver';
                                    @endphp

                                    @if ($isAdmin)
                                        <button type="button" class="btn btn-sm {{ $btnClass }}"
                                            title="{{ $raw }}" {{-- Livewire v3: despachar evento al componente que escucha --}}
                                            wire:click="$dispatch('production.open', {
                                                    docId: {{ $row['id_production_order'] }},
                                                    status: '{{ $status }}',
                                                    action: '{{ $action }}'
                                                })">
                                            {{ $label }}
                                        </button>
                                    @else
                                        {{-- Para Asesor: sigue abriendo el modal pero en modo lectura --}}
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            wire:click="$dispatch('production.open', {
                                                    docId: {{ $row['id_production_order'] }},
                                                    status: '{{ $status }}',
                                                    action: 'ver'
                                                })">
                                            {{ $label }}
                                        </button>
                                    @endif
                                </td>


                                <td class="px-2 py-1 text-right">
                                    <select class="h-8 rounded border border-zinc-300 px-2 text-[11px] bg-white"
                                        onchange="Livewire.dispatch('production.open', { docId: {{ $row['id_production_order'] }}, action: this.value = '' })">
                                        <option value="">Acción…</option>

                                        @if ($isAdmin)
                                            {{-- Solo lectura para Asesor, sin importar el estado --}}
                                            <option value="programar">Ver</option>
                                        @else
                                            @switch($row['status'] ?? '')
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


                                <td class="px-2 py-1 whitespace-nowrap">
                                    {{ \Illuminate\Support\Str::of($row['updated_at'] ?? '')->replace('T', ' ')->beforeLast('.') }}
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-2 py-6 text-center text-zinc-500">Sin registros en
                                        producción.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- Paginación PRODUCCIÓN --}}
                    <div class="pt-2 text-xs">
                        {{ $prodRowsPaginated->onEachSide(1)->links() }}
                    </div>


                </div>
            @endif


            <div class="modal fade" id="pendientesObservationModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow-lg border border-secondary">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title">Enviar a producción</h5>
                            <button type="button" class="btn-close btn-close-white" id="closeObsBtn"></button>
                        </div>

                        <div class="modal-body">
                            <p class="text-sm text-muted">Agrega una observación antes de enviar a producción:</p>
                            <textarea wire:model.defer="observationText" class="form-control" rows="4"
                                placeholder="Ejemplo: revisar medidas, enviar con prioridad..."></textarea>
                        </div>

                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCloseObs">Cancelar</button>
                            <button wire:click="confirmObservation" class="btn btn-primary btn-sm">Enviar</button>
                        </div>

                        {{-- 🔹 Contenedor del Toast --}}
                        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
                            <div id="toastMsg" class="toast align-items-center border-0 text-bg-primary" role="alert"
                                aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body" id="toastText">Mensaje del sistema</div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                        data-bs-dismiss="toast"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @push('scripts')
                <script>
                    document.addEventListener('livewire:initialized', () => {
                        const el = document.getElementById('pendientesObservationModal');
                        if (!el) return;
                        const modal = new bootstrap.Modal(el, {
                            backdrop: 'static'
                        });

                        // Abrir / cerrar modal
                        window.addEventListener('ui:show-observation-pendientes', () => modal.show());
                        window.addEventListener('ui:hide-observation-pendientes', () => modal.hide());

                        document.getElementById('closeObsBtn')?.addEventListener('click', () => modal.hide());
                        document.getElementById('btnCloseObs')?.addEventListener('click', () => modal.hide());
                    });

                    // 🚨 Escucha el evento toast y muestra un alert nativo
                    window.addEventListener('toast', (e) => {
                        const d = e.detail || {};
                        const msg = d.message || 'Acción realizada correctamente.';
                        alert(msg);
                    });
                </script>
            @endpush

            {{-- Fin TAB Producción --}}

            <livewire:order.production-action-modal />







        </div>



    </div>
