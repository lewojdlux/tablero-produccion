<div class="modal fade" id="myModal" tabindex="-1" role="dialog" wire:ignore.self aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"> {{-- ancho mayor para más info --}}
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          Detalle de la orden
          @if($selectedDoc) <small class="text-muted">OP: {{ $selectedDoc }}</small> @endif
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                wire:click="$dispatch('close-order-detail')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        {{-- HEADER: datos generales del documento --}}
        @php($h = $detail['header'] ?? [])
        @if(empty($h))
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
                <dd class="col-7">{{ ($h['Periodo'] ?? '—') . ' / ' . ($h['Ano'] ?? '—') }}</dd>

                <dt class="col-5">Observaciones</dt>
                <dd class="col-7">{{ $h['Observaciones'] ?? '—' }}</dd>
              </dl>
            </div>
          </div>

          {{-- LÍNEAS: productos y estado de facturación --}}
          <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered">
              <thead class="thead-light">
                <tr>
                  <th style="width:50%">Producto</th>
                  <th style="width:25%">Estado factura</th>
                  <th style="width:25%">N° Factura</th>
                </tr>
              </thead>
              <tbody>
                @forelse($detail['lines'] as $line)
                  <tr>
                    <td>{{ $line['Luminaria'] ?? '—' }}</td>
                    <td>{{ $line['EstadoFactura'] ?? '—' }}</td>
                    <td>{{ $line['NFactura'] ?? '—' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted">Sin líneas.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        @endif
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary"
                data-dismiss="modal"
                wire:click="$dispatch('close-order-detail')">Cerrar</button>
      </div>
    </div>
  </div>
</div>
