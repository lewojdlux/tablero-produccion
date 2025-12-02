<div>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" wire:ignore.self aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if (!$showingFicha)
                            Detalle de la orden
                            @if ($selectedDoc)
                                <small class="text-muted">OP: {{ $selectedDoc }}</small>
                            @endif
                        @else
                            Ficha de Ensamble / Insumos
                            <small class="text-muted">OP: {{ $selectedDoc }}</small>
                        @endif
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" wire:click="$dispatch('close-order-detail')">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- 🔹 Vista 1: Detalle General --}}
                    @if (!$showingFicha)
                        @php($h = $detail['header'] ?? [])
                        @if (empty($h))
                            <div class="py-4 text-center text-muted">Cargando detalle…</div>
                        @else
                            <div class="row text-sm">
                                <div class="col-md-6">
                                    <dl class="row mb-2">
                                        <dt class="col-5">Pedido</dt>
                                        <dd class="col-7">{{ $h['Pedido'] ?? '—' }}</dd>
                                        <dt class="col-5">Cliente</dt>
                                        <dd class="col-7">{{ $h['Tercero'] ?? '—' }}</dd>
                                        <dt class="col-5">Asesor</dt>
                                        <dd class="col-7">{{ $h['Vendedor'] ?? '—' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-2">
                                        <dt class="col-5">Fecha OP</dt>
                                        <dd class="col-7">{{ $h['FechaOrdenProduccion'] ?? '—' }}</dd>
                                        <dt class="col-5">Periodo/Año</dt>
                                        <dd class="col-7">{{ ($h['Periodo'] ?? '—').' / '.($h['Ano'] ?? '—') }}</dd>
                                        <dt class="col-5">Observaciones</dt>
                                        <dd class="col-7">{{ $h['Observaciones'] ?? '—' }}</dd>
                                    </dl>
                                </div>
                            </div>

                            {{-- Productos --}}
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Estado factura</th>
                                            <th>N° Factura</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($detail['lines'] as $line)
                                            <tr>
                                                <td>
                                                    <button
                                                        wire:click="$dispatch('open-ficha-desde-modal', { ndoc: {{ $selectedDoc }}, producto: '{{ $line['Luminaria'] }}' })"
                                                        class="btn btn-dark btn-sm">
                                                        {{ $line['Luminaria'] ?? '—' }}
                                                    </button>
                                                </td>
                                                <td>{{ number_format($line['Cantidad'], 0) }}</td>
                                                <td>{{ $line['EstadoFactura'] ?? '—' }}</td>
                                                <td>{{ $line['NFactura'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @else
                        {{-- 🔹 Vista 2: Ficha de Ensamble --}}
                        <h6 class="mb-3">Detalle de la ficha</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
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
                                                <td>{{ $line['Luminaria'] ?? $line['Producto'] }}</td>
                                                <td class="text-end">{{ number_format($line['Cantidad'] ?? 0, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="2" class="text-center text-muted">Sin insumos registrados.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    @if ($showingFicha)
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="volverDetalle">
                            Volver
                        </button>
                    @endif

                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        wire:click="$dispatch('close-order-detail')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            window.addEventListener('show-order-modal', () => $('#myModal').modal('show'));
            window.addEventListener('hide-order-modal', () => $('#myModal').modal('hide'));
        </script>
    @endPushOnce
</div>
