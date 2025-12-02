<div>
    {{-- In work, do what you enjoy. --}}
    <div class="container py-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Notificaciones</h5>

                <div>
                    <div class="btn-group me-2" role="group">
                        <button wire:click="setTab('unread')"
                            class="btn btn-sm {{ $tab === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            Nuevas
                        </button>
                        <button wire:click="setTab('all')"
                            class="btn btn-sm {{ $tab === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            Todas
                        </button>
                        <button wire:click="setTab('read')"
                            class="btn btn-sm {{ $tab === 'read' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            Leídas
                        </button>
                    </div>

                    <select wire:model="perPage" class="form-select form-select-sm d-inline-block" style="width:auto">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="30">30</option>
                    </select>

                    <button wire:click="markAllRead" class="btn btn-sm btn-outline-success ms-2">Marcar todas como
                        leídas</button>
                </div>
            </div>

            <div class="card-body p-0">
                @if (session()->has('message'))
                    <div class="alert alert-success mb-0">{{ session('message') }}</div>
                @endif

                <div class="list-group list-group-flush">
                    @forelse($notifications as $n)
                        @php
                            $data = (array) $n->data;
                            $isUnread = is_null($n->read_at);
                            $title = $data['title'] ?? ($data['descripcion'] ?? 'Notificación');
                            $subtitle = $data['orden_trabajo_id'] ?? null;
                            $url = $data['url'] ?? null;
                        @endphp

                        <div
                            class="list-group-item d-flex justify-content-between align-items-start {{ $isUnread ? 'bg-light' : '' }}">
                            <div style="flex:1; min-width:0;">
                                <div class="fw-semibold small text-truncate">
                                    {{ \Illuminate\Support\Str::limit($title, 120) }}
                                </div>
                                <div class="small text-muted">
                                    @if ($subtitle)
                                        O.T: {{ $subtitle }} ·
                                    @endif {{ $n->created_at->diffForHumans() }}
                                    @if (!empty($data['descripcion']) && strlen($data['descripcion']) > 0)
                                        <div class="mt-1 text-muted small">{!! \Illuminate\Support\Str::limit(e($data['descripcion']), 200) !!}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="ms-2 text-end">
                                <div class="btn-group-vertical">
                                    <button wire:click="openNotification('{{ $n->id }}')"
                                        class="btn btn-sm btn-primary mb-1">Abrir</button>
                                    <button wire:click="markAsRead('{{ $n->id }}')"
                                        class="btn btn-sm btn-outline-secondary mb-1">Marcar leído</button>
                                    <button wire:click="deleteNotification('{{ $n->id }}')"
                                        class="btn btn-sm btn-danger">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">No hay notificaciones.</div>
                    @endforelse
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">Mostrando {{ $notifications->firstItem() ?? 0 }} -
                        {{ $notifications->lastItem() ?? 0 }} de {{ $notifications->total() }}</div>
                    <div>{{ $notifications->links() }}</div>
                </div>
            </div>
        </div>
    </div>

</div>
