<div>

    <div class="space-y-4">

        {{-- Header --}}
        <div class="flex items-center justify-between ">
            <h2 class="text-lg font-semibold">Orden de trabajo</h2>

            {{-- Abrir modal desde cliente (sin roundtrip inicial) usando Livewire.dispatch -> lo escucha PHP #[On('ui:open-create')] --}}
             <livewire:notification-bell />

            <button type="button" class="btn btn-outline-dark btn-sm  " wire:click="openErpModal">
                    + Nuevo
            </button>





            <!--<a href="{{ route('notifications.index') }}"
            class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('notifications.index') ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-zinc-100' }}">
                Notificaciones
            </a>-->
        </div>


        {{-- Filtros --}}
        <details class="rounded border border-zinc-200 bg-zinc-50 p-2 pb-3 mb-3" open>
            <summary class="cursor-pointer text-[11px] text-zinc-700 select-none leading-none">Filtros</summary>
            <div class="mt-1.5 flex flex-wrap items-center gap-1 ">
                <input type="text" placeholder="Buscar por nombre / email / usuario"
                    wire:model.live.debounce.400ms="search"
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
        <div class="overflow-x-auto rounded-lg border border-zinc-200 ">
            <table class="w-full text-xs leading-tight">
                <thead>
                    <th class="px-2 py-1 font-medium">Consecutivo</th>
                    <th class="px-2 py-1 font-medium">Asesor</th>
                    <th class="px-2 py-1 font-medium">Instalador</th>
                    <th class="px-2 py-1 font-medium">Cliente</th>
                    <th class="px-2 py-1 font-medium">Estado</th>
                    <th class="px-2 py-1 font-medium text-center">Acciones</th>
                </thead>
                <tbody class="[&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-zinc-50">
                    @forelse($workOrders as $workOrder)
                        <tr class="border-b border-zinc-200 hover:bg-zinc-50">
                            <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->n_documento }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->vendedor }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                {{ $workOrder->instalador?->nombre_instalador ?? '' }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                {{ $workOrder->tercero }}
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                @if ($workOrder->status === 'pending' && $isAdmin)
                                    <span class="btn btn-sm btn-danger disabled" tabindex="-1">Pendiente</span>
                                @elseif ($workOrder->status === 'in_progress' && $isAdmin)
                                    <span class="btn btn-sm btn-warning disabled" tabindex="-1">En progreso</span>
                                @elseif ($workOrder->status === 'completed' && $isAdmin)
                                    <span class="btn btn-sm btn-success disabled" tabindex="-1">Completado</span>
                                @elseif ($workOrder->status === 'assigned' && $isInstalador && $isAdmin)
                                    <span class="btn btn-sm btn-warning disabled" tabindex="-1">Asignado</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->created_at?->format('d-m-Y') }}</td>
                            <td class="px-2 py-1">
                                @if ($isInstalador)
                                    <div class="flex justify-center gap-2">
                                        <button wire:click="openEditWorkOrder({{ $workOrder->id_work_order }})"
                                            type="button" class="btn btn-outline-secondary btn-sm">
                                            Iniciar
                                        </button>
                                    </div>
                                @endif

                                @if ($isAdmin)
                                    <div class="flex justify-center gap-2">


                                        <button wire:click="openViewWorkOrder({{ $workOrder->id_work_order }})"
                                            type="button" class="btn btn-outline-primary btn-sm">
                                            Asignar Material
                                        </button>

                                        {{-- Abrir edit desde cliente usando Livewire.dispatch -> #[On('ui:open-edit')] --}}
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                            onclick="Livewire.dispatch('ui:open-edit', { id: {{ $workOrder->id_work_order }} })">
                                            Editar
                                        </button>
                                    </div>
                                @endif

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

        <div class="pt-2 text-xs">{{ $workOrders->onEachSide(1)->links() }}</div>


        {{-- ---------------- Modal ERP ---------------- --}}
        <!-- Modal markup (Bootstrap) -->
        <div id="erpModal" class="modal fade" tabindex="-1" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Buscar órdenes en ERP</h5>
                        <button type="button" class="btn-close" aria-label="Close"
                            onclick="Livewire.emit('ui:hide-erp-modal')"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Filtros --}}
                        <div class="row mb-3 g-2">
                            <div class="col-md-3">
                                <label class="form-label small">Fecha inicio</label>
                                <input wire:model.defer="erp_start" type="text" class="form-control form-control-sm"
                                    placeholder="dd/mm/yyyy">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fecha fin</label>
                                <input wire:model.defer="erp_end" type="text" class="form-control form-control-sm"
                                    placeholder="dd/mm/yyyy">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Pedido</label>
                                <input wire:model.defer="erp_pedido" type="text"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Cliente</label>
                                <input wire:model.defer="erp_cliente" type="text"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Asesor</label>
                                <input wire:model.defer="erp_asesor" type="text"
                                    class="form-control form-control-sm">
                            </div>

                            <div class="col-md-3 d-flex align-items-end">
                                <button wire:click="loadErp" class="btn btn-sm btn-primary me-2">Buscar</button>
                                <button wire:click="resetErpFilters();"
                                    class="btn btn-sm btn-outline-secondary">Limpiar</button>
                            </div>
                        </div>

                        {{-- Resultados paginados --}}
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha registro</th>
                                        <th>Pedido</th>
                                        <th>Cliente</th>
                                        <th>Asesor</th>
                                        <th>Estado factura</th>
                                        <th>Factura</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($erpPaginator as $row)
                                        <tr>
                                            <td>
                                                @if (!empty($row['FechaPedido']))
                                                    {{ \Carbon\Carbon::parse($row['FechaPedido'])->format('d-m-Y') }}
                                                @endif
                                            </td>
                                            <td>{{ $row['Pedido'] ?? '' }}</td>
                                            <td>{{ $row['Tercero'] ?? '' }}</td>
                                            <td>{{ $row['Vendedor'] ?? '' }}</td>
                                            <td>{{ $row['EstadoFactura'] ?? '' }}</td>
                                            <td>{{ $row['NFactura'] ?? '' }}</td>
                                            <td class="text-end">

                                                <!-- en la fila / tabla -->
                                                <button wire:click="openInstaladorModal({{ json_encode($row) }})"
                                                    class="btn btn-sm btn-dark"
                                                    data-pedido="{{ $row['Pedido'] ?? ($row['Ndocumento'] ?? '') }}">
                                                    Asignar
                                                </button>



                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-center" colspan="9">Sin resultados</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginación del modal --}}


                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="small">Mostrando {{ $erpPaginator->firstItem() ?? 0 }} -
                                {{ $erpPaginator->lastItem() ?? 0 }} de {{ $erpPaginator->total() }}</div>
                            <div>
                                @if ($erpPaginator->lastPage() > 1)
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            <li
                                                class="page-item {{ $erpPaginator->currentPage() === 1 ? 'disabled' : '' }}">
                                                <a class="page-link" href="#"
                                                    wire:click.prevent="erpGoto({{ $erpPaginator->currentPage() - 1 }})">«</a>
                                            </li>

                                            @for ($p = 1; $p <= $erpPaginator->lastPage(); $p++)
                                                <li
                                                    class="page-item {{ $p === $erpPaginator->currentPage() ? 'active' : '' }}">
                                                    <a class="page-link" href="#"
                                                        wire:click.prevent="erpGoto({{ $p }})">{{ $p }}</a>
                                                </li>
                                            @endfor

                                            <li
                                                class="page-item {{ $erpPaginator->currentPage() === $erpPaginator->lastPage() ? 'disabled' : '' }}">
                                                <a class="page-link" href="#"
                                                    wire:click.prevent="erpGoto({{ $erpPaginator->currentPage() + 1 }})">»</a>
                                            </li>
                                        </ul>
                                    </nav>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary btn-sm"
                            wire:click="closeErpModal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- ---------------- Fin Modal ERP ---------------- --}}



        <!-- Modal instalador -->
        <div wire:ignore.self class="modal fade" id="instaladorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Asignar instalador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small">Pedido</label>
                            {{-- mostramos el pedido via binding, no por JS directo --}}
                            <div class="form-control form-control-sm">{{ $selectedPedido }}</div>
                        </div>



                        <div class="mb-2">
                            <label class="form-label small">Instalador</label>
                            <select wire:model="selectedInstaladorId" class="form-select form-select-sm">
                                <option value="">-- Seleccione instalador --</option>
                                @foreach ($instaladores as $inst)
                                    <option value="{{ $inst['id_instalador'] ?? '' }}">
                                        {{ $inst['nombre_instalador'] ?? '---' }} ({{ $inst['username'] ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="small text-muted">Selecciona el instalador que se asignará a esta OT.</div>

                        {{-- HIDDEN inputs para enviar todos los datos cuando se confirma --}}
                        <input type="hidden" wire:model="selectedPedido">
                        <input type="hidden" wire:model="selectedTercero">
                        <input type="hidden" wire:model="selectedVendedor">
                        <input type="hidden" wire:model="selectedVendedorUsername">
                        <input type="hidden" wire:model="selectedPeriodo">
                        <input type="hidden" wire:model="selectedAno">
                        <input type="hidden" wire:model="selectedNFactura">
                        <input type="hidden" wire:model="selectedObsvPedido">
                        <input type="hidden" wire:model="selectedStatus">
                        <input type="hidden" wire:model="selectedDescription">


                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"
                            wire:click="closeConfirmModal">Cancelar</button>
                        <button type="button" wire:click="confirmAddWorkOrder" class="btn btn-sm btn-primary"
                            wire:loading.attr="disabled" wire:target="confirmAddWorkOrder">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fin Modal instalador -->


        <!-- Modal agregar material (actualizado: sin wire:ignore.self, con Alpine para cerrar dropdown) -->
        <div class="modal fade" id="materialModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Materiales - Orden de trabajo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        @if ($selectedOrder)
                            <h6 class="mb-3">O.T. {{ $selectedOrder->n_documento }}</h6>

                            <!-- Lista de pedidos -->
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-striped text-sm">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Material</th>
                                            <th>Cantidad</th>
                                            <th>Precio</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pedidos as $p)
                                            <tr>
                                                <td>{{ $p->material->codigo_material ?? '-' }}</td>
                                                <td>{{ $p->material->nombre_material ?? '-' }}</td>
                                                <td>{{ $p->cantidad }}</td>
                                                <td>{{ $p->precio_unitario }}</td>
                                                <td>
                                                    <button wire:click="removePedido({{ $p->pedido }})"
                                                        class="btn btn-sm btn-danger">Eliminar</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No hay materiales.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- AUTOCOMPLETE: usar Alpine para click-away -->
                            <form wire:submit.prevent="addMaterialManual" class="mb-3">
                                <div class="row g-2">

                                    <!-- AUTOCOMPLETE por tecla: Alpine + $wire.call para keyup inmediato -->
                                    <div class="col-md-6 position-relative" x-data="{ timer: null }" @click.away="$wire.set('showMaterialDropdown', false)">

                                        <label class="form-label small">Material (buscar)</label>

                                        <!-- input: cada keyup llama a Livewire (sin debounced server model) -->
                                        <input id="materialSearchInput"
                                            type="text"
                                            x-on:keyup="$wire.set('materialSearch', $event.target.value)"
                                            class="form-control form-control-sm"
                                            placeholder="Escribe código o nombre (min 2 caracteres)"
                                            autocomplete="off"
                                            @keydown.escape="$wire.set('showMaterialDropdown', false)"
                                            />

                                        <!-- campo oculto que almacena la id real -->
                                        <input type="hidden" wire:model="material_id" />

                                        <!-- lista de sugerencias (posicion absoluta dentro del contenedor relativo) -->
                                        @if ($showMaterialDropdown)
                                            <div class="list-group position-absolute w-100 shadow-sm mt-1"
                                                style="z-index:4000; max-height:260px; overflow:auto;">
                                                @foreach ($materialOptions as $opt)
                                                    <button type="button"
                                                            wire:click.prevent="selectMaterialOption({{ $opt['id_material'] }})"
                                                            class="list-group-item list-group-item-action">
                                                        {{ $opt['codigo_material'] ? $opt['codigo_material'] . ' - ' : '' }}{{ $opt['nombre_material'] }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>


                                    <div class="col-md-1">
                                        <label class="form-label small">Cantidad</label>
                                        <input wire:model="cantidad" type="number" min="1"
                                            class="form-control form-control-sm" />
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Precio unit.</label>
                                        <input wire:model="precio_unitario" type="text"
                                            class="form-control form-control-sm" />
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">Agregar</button>
                                    </div>
                                </div>
                            </form>

                            <!-- Import CSV -->
                            <form wire:submit.prevent="importMaterials">
                                <div class="mb-2">
                                    <label class="form-label small">Importar CSV
                                        (codigo,nombre,cantidad,precio)</label>
                                    <input type="file" wire:model="csvFile" accept=".csv,text/csv"
                                        class="form-control form-control-sm" />
                                    <div wire:loading wire:target="csvFile" class="small text-muted">Subiendo...</div>
                                    @error('csvFile')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-secondary btn-sm">Importar archivo</button>
                            </form>
                        @else
                            <div class="text-muted">Seleccione primero una orden para ver o agregar materiales.</div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary"
                            data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fin Modal agregar material -->



        @push('scripts')
            <script>
                const el = document.getElementById('erpModal');
                let modalInstance;

                window.addEventListener('ui:show-erp-modal', () => {
                    if (!modalInstance) {
                        modalInstance = new bootstrap.Modal(el, {
                            backdrop: 'static', // 👈 no cierra al click fuera
                            keyboard: false // 👈 no cierra con ESC
                        });
                    }
                    modalInstance.show();
                });

                window.addEventListener('ui:hide-erp-modal', () => {
                    if (modalInstance) modalInstance.hide();
                });

                // controlar modal con eventos de Livewire
                const instaladorEl = document.getElementById('instaladorModal');
                let instaladorModalInstance;

                window.addEventListener('ui:show-instalador-modal', () => {
                    if (!instaladorModalInstance) {
                        instaladorModalInstance = new bootstrap.Modal(instaladorEl, {
                            backdrop: 'static', // 👈 no cierra al click fuera
                            keyboard: false // 👈 no cierra con ESC
                        });
                    }
                    instaladorModalInstance.show();
                });

                window.addEventListener('ui:hide-instalador-modal', () => {
                    if (instaladorModalInstance) instaladorModalInstance.hide();
                });



                // Controlar modal con eventos de Livewire agregar material
                const materialEl = document.getElementById('materialModal');
                let materialModalInstance;

                window.addEventListener('ui:open-view-work-order', () => {
                    if (!materialModalInstance) {
                        materialModalInstance = new bootstrap.Modal(materialEl, {
                            backdrop: 'static', // 👈 no cierra al click fuera
                            keyboard: false // 👈 no cierra con ESC
                        });
                    }
                    materialModalInstance.show();
                });

                window.addEventListener('ui:hide-material-modal', () => {
                    if (materialModalInstance) materialModalInstance.hide();
                });
            </script>
        @endpush


    </div>
