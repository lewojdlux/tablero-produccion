<div>
    {{-- Modal Bootstrap --}}
    <div class="modal fade" id="productionActionModal" tabindex="-1" aria-hidden="true" wire:ignore.self
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        @switch($action)
                            @case('programar')
                                Programar producción
                            @break

                            @case('empezar')
                                Empezar producción
                            @break

                            @case('finalizar')
                                Finalizar producción
                            @break

                            @case('aprobar')
                                Aprobar producción
                            @break

                            @case('reabrir')
                                Reabrir producción
                            @break

                            @default
                                Actualizar producción
                        @endswitch
                        <small class="text-muted ms-2">#{{ $docId }}</small>
                        @if ($readOnly)
                            <span class="badge bg-secondary ms-2">Solo lectura</span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" aria-label="Close" wire:click="close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Fecha inicial</label>
                            <input type="date" class="form-control form-control-sm"
                                wire:model.live="fecha_inicial_produccion" readonly>
                            @error('fecha_inicial_produccion')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Hora inicial</label>
                            <input type="time" class="form-control form-control-sm"
                                wire:model.live="hora_inicio_produccion">
                            @error('hora_inicio_produccion')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Fecha final</label>
                            <input type="date" class="form-control form-control-sm"
                                wire:model.live="fecha_final_produccion">
                            @error('fecha_final_produccion')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Hora final</label>
                            <input type="time" class="form-control form-control-sm"
                                wire:model.live="hora_fin_produccion">
                            @error('hora_fin_produccion')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Días</label>
                            <input type="number" min="0" class="form-control form-control-sm"
                                wire:model.model="dias_produccion" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Horas</label>
                            <input type="number" min="0" class="form-control form-control-sm"
                                wire:model.model="horas_produccion" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Minutos</label>
                            <input type="number" min="0" class="form-control form-control-sm"
                                wire:model.model="minutos_produccion" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Segundos</label>
                            <input type="number" min="0" class="form-control form-control-sm"
                                wire:model.model="segundos_produccion" readonly>
                        </div>

                        <!--<div class="col-md-6">
                            <label class="form-label form-label-sm">Cantidad luminarias</label>
                            <input type="number" min="0" class="form-control form-control-sm"
                                wire:model.defer="cantidad_luminarias">
                        </div>-->

                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Nuevo estado</label>
                            <select class="form-select form-select-sm form-control" wire:model.defer="nuevo_status">
                                <option value="">(según acción)</option>
                                <option value="queued">En cola</option>
                                <option value="in_progress">En producción</option>
                                <option value="done">Terminado para entrega</option>
                                <!--<option value="approved">Aprobado</option>-->
                            </select>
                            @error('nuevo_status')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label form-label-sm">Observaciones</label>
                            <textarea rows="3" class="form-control form-control-sm" wire:model.defer="observacion_produccion"></textarea>
                        </div>
                    </div>
                </div>



                <div class="modal-footer">

                    @if ($readOnly)
                        <button type="button" class="btn btn-light btn-sm" wire:click="close">Cancelar</button>
                    @endif
                    @if (!$readOnly)
                        <button type="button" class="btn btn-light btn-sm" wire:click="close">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="save"
                            wire:loading.attr="disabled">
                            Guardar
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- JS de apertura/cierre via eventos --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('productionActionModal');
                let modalInstance;

                // Mostrar modal
                window.addEventListener('ui:show-production-modal', () => {
                    if (!modalInstance) {
                        modalInstance = $(el).modal({
                            backdrop: 'static',
                            keyboard: false
                        });
                    }
                    $(el).modal('show');
                });

                // Ocultar modal
                window.addEventListener('ui:hide-production-modal', () => {
                    try {
                        $(el).modal('hide');
                    } catch (e) {
                        console.error('Error cerrando modal:', e);
                    }
                });
            });
        </script>
    @endpush

</div>
