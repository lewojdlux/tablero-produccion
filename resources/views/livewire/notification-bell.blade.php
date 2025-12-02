<div>
    {{-- The whole world belongs to you. --}}

    <div wire:poll.{{ $pollIntervalMs }}ms="loadNotifications" x-data="{ open: false }" class="position-relative">
        @php
            $isAdmin = in_array((int) (auth()->user()->perfil_usuario_id ?? 0), [1, 2], true);
        @endphp

        @if ($isAdmin)
            <div class="d-inline-block">


                <button @click="open = !open" @click.outside="open = false"
                    class="btn btn-sm btn-outline-secondary position-relative" type="button" aria-haspopup="true">
                    <i class="fa-solid fa-bell"></i>
                    @if ($count > 0)
                        <span class="badge bg-danger position-absolute" style="top:-6px; right:-6px; font-size:10px;">
                            {{ $count }}
                        </span>
                    @endif
                </button>

                <!-- DROPDOWN CARD -->
                <div x-show="open" x-cloak class="card position-absolute end-0 mt-2 shadow"
                    style="width:320px; z-index:5000;">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <strong class="m-0">Notificaciones ({{ $count }})</strong>
                        <button class="btn btn-link btn-sm p-0" wire:click="markAllRead">Marcar todas</button>
                    </div>

                    <div class="card-body p-2" style="max-height:380px; overflow:auto;">
                        @if (empty($unread))
                            <div class="text-center text-muted small py-3">No hay nuevas notificaciones</div>
                        @else
                            {{-- Agrupar por tipo (category) --}}
                            @php
                                $groups = [];
                                foreach ($unread as $n) {
                                    $groups[$n['type']][] = $n;
                                }
                            @endphp

                            @foreach ($groups as $type => $items)
                                <div class="mb-2">
                                    <div class="small text-uppercase text-muted mb-1">{{ ucfirst($type) }} ·
                                        {{ count($items) }}</div>

                                    @foreach ($items as $n)
                                        @php $data = $n['raw'] ?? []; @endphp
                                        <div class="d-flex justify-content-between align-items-center mb-1 p-2 rounded"
                                            style="background:#f8f9fa;">
                                            <div style="flex:1; min-width:0;">
                                                <div class="fw-semibold small text-truncate">
                                                    {{ \Illuminate\Support\Str::limit($n['title'] ?? 'Notificación', 70) }}
                                                </div>
                                                <div class="small text-muted">O.T: {{ $n['orden_trabajo_id'] ?? '-' }} ·
                                                    {{ $n['ago'] }}</div>
                                            </div>
                                            <div class="ms-2">
                                                <button class="btn btn-sm btn-primary"
                                                    wire:click="markAsReadAndGo('{{ $n['id'] }}')">Abrir</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                            <div class="text-center mt-2">
                                <a class="small" href="{{ route('notifications.index') ?? '#' }}">Ver todas</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <script>
                window.addEventListener('notification-redirect', event => {
                    if (event?.detail?.url) {
                        window.location.href = event.detail.url;
                    }
                });
            </script>
        @endif
    </div>


</div>
