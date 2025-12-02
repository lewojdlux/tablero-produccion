<div>
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
                <strong>Orden producción (ensamble, 140):</strong> {{ $h['EnsambleDocumento'] ?? '-' }} <br>
                <strong>Pedido (ensamble):</strong> {{ $h['EnsamblePedido'] ?? '-' }} <br>
                <strong>Cliente (ensamble):</strong> {{ $h['ClienteEnsamble'] ?? '-' }} <br>
                <strong>Asesor (ensamble):</strong> {{ $h['VendedorEnsamble'] ?? '-' }} <br>
                <strong>Fecha ensamble:</strong> {{ $h['FechaEnsamble'] ?? '-' }} <br>
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
                @if(!empty($detail['lines']))
                    @foreach($detail['lines'] as $line)
                    <tr>
                        <td>{{ $line['Producto'] }}</td>
                        <td class="text-end">{{ number_format($line['Cantidad'], 2) }} </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                    <td colspan="2" class="text-center text-muted">Sin insumos registrados.</td>
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


    @push('scripts')
        <script>
            const el = document.getElementById('ensambleModal');
            let modalInstance;

            window.addEventListener('open-order-detail-ficha', () => {
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(el, {
                        backdrop: 'static', // 👈 no cierra al click fuera
                        keyboard: false // 👈 no cierra con ESC
                    });
                }
                modalInstance.show();
            });

            window.addEventListener('ui:hide-production-modal', () => {
                if (modalInstance) modalInstance.hide();
            });
        </script>
    @endpush
</div>
