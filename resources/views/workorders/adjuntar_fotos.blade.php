@extends('layouts.app')

@section('content')
    <style>
        .foto-card {
            aspect-ratio: 1/1;
            overflow: hidden;
            border-radius: 10px;
            background: #f1f3f5;
            position: relative;
            transition: transform .2s ease;
        }

        .foto-card:hover {
            transform: scale(1.03);
        }

        .foto-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .delete-btn {
            position: absolute;
            top: 6px;
            right: 6px;
            background: rgba(0, 0, 0, .6);
            border: none;
            color: #fff;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            font-size: 14px;
        }
    </style>

    <div id="fotoApp" class="container py-4">

        <div v-if="mensaje" class="alert alert-success alert-dismissible fade show" role="alert">
            @{{ mensaje }}
            <button type="button" class="btn-close" @click="mensaje=''"></button>
        </div>

        <div class="card shadow-sm">

            <div class="card-header bg-dark text-white">
                Adjuntar Fotos - OT #{{ $orden->id_work_order }}
            </div>

            {{-- ================= FOTOS REGISTRADAS ================= --}}
            <div class="card-body border-bottom">

                <h6 class="text-muted mb-3">Fotos registradas</h6>

                <div v-if="fotosGuardadas.length === 0" class="text-muted small">
                    No hay fotos registradas aún.
                </div>

                <div class="row g-3">

                    <div class="col-6 col-md-3 col-lg-2" v-for="foto in fotosGuardadas" :key="'guardada-' + foto.id">

                        <div class="foto-card shadow-sm">

                            <template v-if="foto.tipo === 'imagen'">
                                <img :src="foto.url">
                            </template>

                            <template v-else>
                                <video :src="foto.url" controls style="width:100%; height:100%; object-fit:cover;"></video>
                            </template>

                            <button class="delete-btn" @click="eliminarRegistrada(foto.id)">
                                ✕
                            </button>

                        </div>

                    </div>

                </div>

            </div>

            {{-- ================= NUEVA CARGA ================= --}}
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">
                        Puede seleccionar máximo 5 fotos por carga
                    </small>

                    <small class="fw-semibold">
                        @{{ fotosNuevas.length }} / 5
                    </small>
                </div>

                {{-- PREVIEW --}}
                <div v-if="fotosNuevas.length > 0" class="row g-3 mb-3">

                    <div class="col-6 col-md-3 col-lg-2" v-for="(foto, index) in fotosNuevas" :key="index">

                        <div class="foto-card shadow-sm">

                            <template v-if="foto.tipo === 'imagen'">
                                <img :src="foto.preview">
                            </template>

                            <template v-else>
                                <video :src="foto.preview" controls style="width:100%; height:100%; object-fit:cover;"></video>
                            </template>

                            <button class="delete-btn" @click="eliminarFoto(index)">
                                ✕
                            </button>

                        </div>

                    </div>

                </div>

                {{-- INPUT --}}
                <input type="file" multiple accept="image/*,video/*" class="form-control mb-3" @change="seleccionarFotos">

                <div class="d-flex justify-content-between">

                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                        Volver
                    </a>

                    <button type="button" class="btn btn-dark" :disabled="loading || fotosNuevas.length === 0"
                        @click="guardar">

                        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>

                        Subir Fotos
                    </button>

                </div>

            </div>

        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

    <script>
        const fotosGuardadas = @json($fotos ?? []);
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const {
                createApp
            } = Vue;

            createApp({

                data() {
                    return {
                        fotosNuevas: [],
                        fotosGuardadas: Array.isArray(fotosGuardadas) ? fotosGuardadas : [],
                        loading: false,
                        mensaje: ''
                    }
                },

                methods: {

                    seleccionarFotos(event) {

                        const archivos = Array.from(event.target.files);
                        const permitidas = archivos.slice(0, 5);

                        this.fotosNuevas = [];

                        permitidas.forEach(file => {

                            const reader = new FileReader();

                            reader.onload = (e) => {
                                const tipo = file.type && file.type.startsWith('video') ? 'video' : 'imagen';

                                this.fotosNuevas.push({
                                    file: file,
                                    preview: e.target.result,
                                    tipo: tipo
                                });
                            };

                            reader.readAsDataURL(file);
                        });

                        event.target.value = '';
                    },

                    eliminarFoto(index) {
                        this.fotosNuevas.splice(index, 1);
                    },

                    async eliminarRegistrada(id) {

                        if (!confirm('¿Eliminar esta foto?')) return;

                        try {

                            const response = await fetch(
                                `/ordenes-trabajo/foto/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                }
                            );

                            const data = await response.json();

                            if (response.ok && data.success) {

                                this.fotosGuardadas =
                                    this.fotosGuardadas.filter(f => f.id !== id);

                                this.mensaje = 'Foto eliminada correctamente';
                            }

                        } catch (error) {
                            alert('Error eliminando foto');
                        }
                    },

                    async guardar() {

                        if (this.fotosNuevas.length === 0) return;

                        this.loading = true;

                        const formData = new FormData();

                        this.fotosNuevas.forEach(f => {
                            formData.append('fotos[]', f.file);
                        });

                        try {

                            const response = await fetch(
                                "{{ route('workorders.guardar.fotos', $orden->id_work_order) }}", {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: formData
                                }
                            );

                            const data = await response.json();

                            if (!response.ok) {
                                alert(data.message || 'Error al subir archivos');
                                this.loading = false;
                                return;
                            }

                            if (data.success) {
                                this.fotosGuardadas = data.fotos;
                                this.fotosNuevas = [];
                                this.mensaje = 'Archivos subidos correctamente ✅';
                            }

                        } catch (error) {
                            alert("Error inesperado");
                        }

                        this.loading = false;
                    }

                }

            }).mount('#fotoApp');

        });
    </script>
@endpush
