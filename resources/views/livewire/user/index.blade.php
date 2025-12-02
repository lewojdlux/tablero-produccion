<div>

    <div class="space-y-4">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Usuarios</h2>

            {{-- Abrir modal desde cliente (sin roundtrip inicial) usando Livewire.dispatch -> lo escucha PHP #[On('ui:open-create')] --}}
            <button type="button" class="btn btn-outline-dark btn-sm" onclick="Livewire.dispatch('ui:open-create')">
                + Nuevo
            </button>
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

        @if ($modalFlash)
            <div class="alert alert-{{ $modalFlashType }} alert-dismissible fade show" role="alert">
                {{ $modalFlash }}
                <button type="button" class="btn-close" aria-label="Close" wire:click="$set('modalFlash', null)"></button>
            </div>
        @endif

        {{-- Tabla --}}
        <div class="overflow-x-auto rounded-lg border border-zinc-200 ">
            <table class="w-full text-xs leading-tight">
                <thead>
                    <th class="px-2 py-1 font-medium">Nombre</th>
                    <th class="px-2 py-1 font-medium">Email</th>
                    <th class="px-2 py-1 font-medium">Usuario</th>
                    <th class="px-2 py-1 font-medium">Perfil</th>
                    <th class="px-2 py-1 font-medium">Estado</th>
                    <th class="px-2 py-1 font-medium">Creado</th>
                    <th class="px-2 py-1 font-medium text-end">Acciones</th>
                </thead>
                <tbody class="[&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-zinc-50">
                    @forelse($users as $u)
                        <tr class="border-b border-zinc-200 hover:bg-zinc-50">
                            <td class="px-2 py-1 whitespace-nowrap">{{ $u->name }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $u->email }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $u->username }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                @php $p=(int)($u->perfil_usuario_id ?? 2); @endphp
                                @if ($p === 1)
                                    <span class="btn btn-sm btn-primary disabled" tabindex="-1">Admin</span>
                                @elseif($p === 2)
                                    <span class="btn btn-sm btn-info disabled" tabindex="-1">Super</span>
                                @else
                                    <span class="btn btn-sm btn-secondary disabled" tabindex="-1">Perfil
                                        {{ $p }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                @if ($u->estado)
                                    <span class="btn btn-sm btn-success disabled" tabindex="-1">Activo</span>
                                @else
                                    <span class="btn btn-sm btn-secondary disabled" tabindex="-1">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $u->created_at?->format('d-m-Y') }}</td>
                            <td class="px-2 py-1">
                                <div class="flex justify-end gap-2">

                                    {{-- Abrir edit desde cliente usando Livewire.dispatch -> #[On('ui:open-edit')] --}}
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="Livewire.dispatch('ui:open-edit', { id: {{ $u->id }} })">
                                        Editar
                                    </button>

                                    <button wire:click="toggleActive({{ $u->id }})" type="button"
                                        class="btn btn-outline-secondary btn-sm">
                                        {{ $u->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                    <button wire:click="confirmDelete({{ $u->id }})" type="button"
                                        class="btn btn-outline-danger btn-sm">Eliminar</button>
                                </div>
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

        <div class="pt-2 text-xs">{{ $users->onEachSide(1)->links() }}</div>

        {{-- ============ Modal SIEMPRE en el DOM ============ --}}
        <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true" wire:ignore.self
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $editingId ? 'Editar usuario' : 'Nuevo usuario' }}
                            @if ($editingId)
                                <small class="text-muted ms-2">#{{ $editingId }}</small>
                            @endif
                        </h5>
                        <button type="button" class="btn-close" aria-label="Close" wire:click="close"></button>
                    </div>

                    <div class="modal-body">
                        @if ($modalFlash)
                            <div class="alert alert-{{ $modalFlashType }} alert-dismissible fade show" role="alert">
                                {{ $modalFlash }}
                                <button type="button" class="btn-close" aria-label="Close" wire:click="$set('modalFlash', null)"></button>
                            </div>
                        @endif

                        <div class="row g-3">
                            {{-- campos ... (igual que tenías) --}}
                            <div class="col-md-6">
                                <label class="form-label form-label-sm">Nombre</label>
                                <input type="text" class="form-control form-control-sm" wire:model.defer="name">
                                @error('name')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm">Email</label>
                                <input type="email" class="form-control form-control-sm" wire:model.defer="email">
                                @error('email')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm">Usuario</label>
                                <input type="text" class="form-control form-control-sm" wire:model.defer="username">
                                @error('username')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm">Perfil</label>
                                <select class="form-select form-select-sm" wire:model.live="perfil_usuario_id">
                                    @foreach ($perfiles as $p)
                                        <option value="{{ $p->id_perfil_usuario }}">{{ $p->nombre_perfil }}</option>
                                    @endforeach
                                </select>
                                @error('perfil_usuario_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            @if ($perfilAsesorId !== null && (int)$perfil_usuario_id === (int)$perfilAsesorId)
                                <div class="col-md-6">
                                    <label class="form-label form-label-sm">Identificador asesor (numérico)</label>
                                    <input type="number" class="form-control form-control-sm"
                                        wire:model.defer="identificador_asesor" min="1">
                                    @error('identificador_asesor')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input id="estado" type="checkbox" class="form-check-input"
                                        wire:model.defer="estado">
                                    <label for="estado" class="form-check-label">Activo</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label form-label-sm">
                                    {{ $editingId ? 'Cambiar contraseña (opcional)' : 'Contraseña' }}
                                </label>
                                <input type="password" class="form-control form-control-sm"
                                    wire:model.defer="password">
                                @error('password')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label form-label-sm">Confirmar contraseña</label>
                                <input type="password" class="form-control form-control-sm"
                                    wire:model.defer="password_confirmation">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" wire:click="close">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="save"
                            wire:loading.attr="disabled">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            const el = document.getElementById('userModal');
            let modalInstance;

            window.addEventListener('ui:show-user-modal', () => {
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(el, {
                        backdrop: 'static', // 👈 no cierra al click fuera
                        keyboard: false // 👈 no cierra con ESC
                    });
                }
                modalInstance.show();
            });

            window.addEventListener('ui:hide-user-modal', () => {
                if (modalInstance) modalInstance.hide();
            });
        </script>
    @endpush


</div>
