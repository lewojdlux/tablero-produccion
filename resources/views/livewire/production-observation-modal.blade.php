<div>
    {{-- 🗒️ Modal para agregar observación --}}
    <div class="modal fade" id="observationModal" tabindex="-1" aria-hidden="true" wire:ignore.self style="z-index:1065;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border border-secondary">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Agregar observación</h5>
                    <button type="button" class="btn-close btn-close-white" id="closeObservationBtn"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted mb-2">Agrega una observación para el ticket:
                        <strong>{{ $ticket }}</strong>
                    </p>

                    <div class="mb-3">
                        <label class="form-label text-sm text-secondary">Observación</label>
                        <textarea wire:model.defer="observation" class="form-control" rows="4"
                            placeholder="Escribe la observación aquí..."></textarea>

                        @error('observation')
                            <small class="text-danger">{{ $docId }}</small>
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" id="btnCloseObservation">Cancelar</button>
                    <button type="button" wire:click="save" class="btn btn-primary btn-sm">
                        Guardar observación
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Script para controlar modal Bootstrap --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const obsEl = document.getElementById('observationModal');
                const obsModal = new bootstrap.Modal(obsEl);

                window.addEventListener('ui:show-observation-modal', () => {
                    obsModal.show();
                });

                window.addEventListener('ui:hide-observation-modal', () => {
                    obsModal.hide();
                });

                document.getElementById('closeObservationBtn').addEventListener('click', () => obsModal.hide());
                document.getElementById('btnCloseObservation').addEventListener('click', () => obsModal.hide());
            });
        </script>
    @endpush
</div>
