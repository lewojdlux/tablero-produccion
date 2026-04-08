@extends('layouts.app')

@section('content')
<style>
    [v-cloak] {
        display: none;
    }
</style>
<div id="visitasApp" class="container py-4" v-cloak>
    <div v-if="mensaje" class="alert alert-success">
        @{{ mensaje }}
    </div>

    <div v-if="error" class="alert alert-danger">
        @{{ error }}
    </div>


    <div class="card shadow-sm">

        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">

            <div class="d-flex align-items-center gap-2">
                <button onclick="history.back()" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-arrow-left"></i>
                </button>

                <div>
                    <strong>OT #{{ $orden->id_work_order }}</strong>
                    <br>
                    <small class="text-light">
                        Cliente: {{ $orden->tercero }}
                    </small>
                </div>
            </div>

            <span class="badge bg-success">
                VISITAS
            </span>

        </div>

        <div class="card-body">

            <!-- FORM -->
            <div class="row g-3 mb-4">

                <div class="col-md-3">
                    <label class="form-label small text-muted">Fecha visita</label>
                    <input type="date" v-model="fecha" class="form-control">
                </div>

                <div class="col-md-12">
                    <label class="form-label small text-muted">Observación</label>
                    <textarea v-model="observacion"
                            class="form-control"
                            rows="2"
                            placeholder="Detalle de la visita..."></textarea>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-dark w-100"
                            :disabled="loadingGuardar"
                            @click="guardar">

                        <span v-if="loadingGuardar" class="spinner-border spinner-border-sm me-1"></span>

                        <span v-if="!loadingGuardar">
                            <i class="fas fa-save me-1"></i> Guardar
                        </span>

                        <span v-else>
                            Registrando...
                        </span>
                    </button>
                </div>

            </div>

            <!-- LISTADO -->
            <div v-if="visitas.length === 0" class="text-muted text-center py-3">
                <i class="fas fa-info-circle"></i> No hay visitas registradas
            </div>

            <div class="list-group">

                <div class="list-group-item d-flex justify-content-between align-items-start"
                    v-for="visita in visitas" :key="visita.id">

                    <div>

                        <div class="fw-bold text-dark">
                            <i class="fas fa-calendar-alt text-primary me-1"></i>
                            @{{ visita.fecha_visita }}
                        </div>

                        <div class="text-muted small mt-1">
                            @{{ visita.observacion || 'Sin observación' }}
                        </div>

                    </div>

                    <button class="btn btn-sm btn-outline-danger"
                            :disabled="loadingEliminar === visita.id"
                            @click="eliminar(visita.id)">

                        <span v-if="loadingEliminar === visita.id"
                            class="spinner-border spinner-border-sm"></span>

                        <i v-else class="fas fa-trash"></i>
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const { createApp } = Vue;

    createApp({

        data() {
            return {
                visitas: [],
                fecha: '',
                observacion: '',
                orderId: {{ $orden->id_work_order }},
                loadingGuardar: false,
                loadingEliminar: null,
                mensaje: '',
                error: ''

            }
        },

        mounted() {
            this.cargar();
        },

        methods: {

            async cargar() {
                const res = await fetch(`/ordenes-trabajo/${this.orderId}/visitas`);
                this.visitas = await res.json();
            },

            async guardar() {

                this.error = '';
                this.mensaje = '';

                if (!this.fecha) {
                    this.error = 'Debe seleccionar fecha';
                    return;
                }

                this.loadingGuardar = true;

                try {

                    const res = await fetch(`/ordenes-trabajo/${this.orderId}/visitas`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            fecha_visita: this.fecha,
                            observacion: this.observacion
                        })
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        this.error = data.message || 'Error al registrar';
                        return;
                    }

                    this.mensaje = 'Visita registrada correctamente ✅';
                    this.fecha = '';
                    this.observacion = '';

                    await this.cargar();

                    setTimeout(() => this.mensaje = '', 3000);

                } catch (e) {
                    this.error = 'Error inesperado al registrar';
                }

                this.loadingGuardar = false;
            },

            async eliminar(id) {

                if (!confirm('¿Eliminar visita?')) return;

                this.error = '';
                this.mensaje = '';
                this.loadingEliminar = id;

                try {

                    const res = await fetch(`/ordenes-trabajo/visitas/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        this.error = data.message || 'Error al eliminar';
                        return;
                    }

                    this.mensaje = 'Visita eliminada correctamente 🗑️';

                    await this.cargar();

                    setTimeout(() => this.mensaje = '', 3000);

                } catch (e) {
                    this.error = 'Error inesperado al eliminar';
                }

                this.loadingEliminar = null;
            }

        }

    }).mount('#visitasApp');

});
</script>
@endpush