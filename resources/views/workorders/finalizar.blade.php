@extends('layouts.app')

@section('content')
    <style>
        [v-cloak] {
            display: none;
        }
    </style>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-11 col-xl-10">

                <div class="bg-white p-4 rounded shadow">

                    <h3 class="mb-3">
                        Finalizar Orden de Trabajo #{{ $ordenTrabajo->n_documento }}
                    </h3>

                    <div class="mb-4 text-sm">
                        <p><strong>Cliente:</strong> {{ $ordenTrabajo->tercero }}</p>
                        <p><strong>Pedido de venta:</strong> {{ $ordenTrabajo->pedido ?? '—' }}</p>
                        <p><strong>Instalador:</strong> {{ optional($ordenTrabajo->instalador)->nombre_instalador }}</p>
                    </div>



                    {{-- VUE APP --}}
                    <div id="finalizarOT" v-cloak data-orden-id="{{ $ordenTrabajo->id_work_order }}"
                        data-csrf="{{ csrf_token() }}"
                        data-post-url="{{ route('workorders.otjornada', $ordenTrabajo->id_work_order) }}"
                        data-get-url="{{ route('workorders.jornadas', $ordenTrabajo->id_work_order) }}"
                        data-finalizar-url="{{ route('workorders.finalizar', $ordenTrabajo->id_work_order) }}">

                        <div v-if="alert.show"
                            class="mb-3 p-3 rounded"
                            :class="{
                                'bg-green-100 text-green-800 border border-green-300': alert.type === 'success',
                                'bg-red-100 text-red-800 border border-red-300': alert.type === 'error',
                                'bg-yellow-100 text-yellow-800 border border-yellow-300': alert.type === 'warning'
                            }">
                            @{{ alert.message }}
                        </div>


                        {{-- ================= JORNADAS REGISTRADAS ================= --}}
                        <div v-if="jornadasRegistradas.length" class="mb-4">
                            <h5 class="mb-2">📅 Jornadas registradas</h5>

                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora inicio</th>
                                        <th>Hora fin</th>
                                        <th>Horas</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(j, i) in jornadasRegistradas" :key="i">
                                        <td>@{{ j.fecha }}</td>
                                        <td>@{{ j.hora_inicio }}</td>
                                        <td>@{{ j.hora_fin }}</td>
                                        <td>@{{ j.horas_trabajadas }}</td>
                                        <td>@{{ j.observaciones }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- ================= NUEVA JORNADA ================= --}}
                        <form @submit.prevent="submit" v-if="!otFinalizada">

                            <h5 class="mb-3">➕ Registrar nueva jornada</h5>

                            <div v-for="(j, index) in jornadas" :key="index"
                                class="border rounded p-3 mb-3 bg-light">

                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label>Fecha</label>
                                        <input type="date" class="form-control" v-model="j.fecha" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label>Hora inicio</label>
                                        <input type="time" class="form-control" v-model="j.hora_inicio" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label>Hora fin</label>
                                        <input type="time" class="form-control" v-model="j.hora_fin" required>
                                    </div>

                                    <div class="col-md-2 d-flex align-items-end">
                                        <button v-if="jornadas.length > 1" type="button" class="btn btn-danger btn-sm"
                                            @click="removeJornada(index)">
                                            Quitar
                                        </button>
                                    </div>
                                </div>

                                <textarea class="form-control" rows="2" v-model="j.observaciones" placeholder="Observaciones (opcional)">
                            </textarea>
                            </div>



                            <div class="mb-4">
                                <label>Descripción general / Novedades</label>
                                <textarea class="form-control" rows="4" v-model="installationNotes" >
                            </textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('ordenes.trabajo.asignados') }}" class="btn btn-secondary">
                                    Cancelar
                                </a>

                                <button class="btn btn-success">
                                    Guardar jornada
                                </button>

                                <button type="button" class="btn btn-danger" @click="finalizarOT">
                                    Finalizar OT
                                </button>


                            </div>

                        </form>
                        <div v-else class="alert alert-success">
                            Esta orden de trabajo ya fue finalizada.
                            No se pueden registrar más jornadas.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        const {
            createApp,
            ref,
            onMounted
        } = Vue;

        createApp({
            setup() {

                const el = document.getElementById('finalizarOT');

                const csrfToken = el.dataset.csrf;
                const postUrl = el.dataset.postUrl;
                const getUrl = el.dataset.getUrl;
                const finalizarUrl = el.dataset.finalizarUrl;


                const alert = ref({
                    show: false,
                    type: 'success', // success | error | warning
                    message: ''
                });

                const jornadasRegistradas = ref([]);
                const jornadas = ref([{
                    fecha: '',
                    hora_inicio: '',
                    hora_fin: '',
                    observaciones: ''
                }]);

                const installationNotes = ref('');
                const otFinalizada = ref(false);

                async function cargarJornadas() {
                    const res = await axios.get(getUrl);
                    jornadasRegistradas.value = res.data;
                }

                async function submit() {
                    try {
                        const res = await axios.post(
                            postUrl,
                            { jornadas: jornadas.value },
                            { headers: { 'X-CSRF-TOKEN': csrfToken } }
                        );

                        await cargarJornadas();

                        jornadas.value = [{
                            fecha: '',
                            hora_inicio: '',
                            hora_fin: '',
                            observaciones: ''
                        }];

                        alert.value = {
                            show: true,
                            type: res.data.type,
                            message: res.data.message
                        };

                    } catch (e) {
                        alert.value = {
                            show: true,
                            type: 'error',
                            message: e.response?.data?.message || 'Error al guardar la jornada'
                        };
                    }
                }

                async function finalizarOT() {
                    if (!installationNotes.value) {
                        alert.value = {
                            show: true,
                            type: 'warning',
                            message: 'Debe ingresar la descripción general.'
                        };
                        return;
                    }

                    if (!confirm('¿Finalizar la orden de trabajo?')) return;

                    try {
                        const res = await axios.post(
                            finalizarUrl,
                            { installation_notes: installationNotes.value },
                            { headers: { 'X-CSRF-TOKEN': csrfToken } }
                        );

                        alert.value = {
                            show: true,
                            type: res.data.type,
                            message: res.data.message
                        };

                        otFinalizada.value = true;

                        setTimeout(() => {
                            window.location.href = "{{ route('ordenes.trabajo.asignados') }}";
                        }, 1500);

                    } catch (e) {
                        alert.value = {
                            show: true,
                            type: 'error',
                            message: e.response?.data?.message || 'Error al finalizar la OT'
                        };
                    }
                }


                onMounted(cargarJornadas);

                return {
                    jornadasRegistradas,
                    jornadas,
                    installationNotes,
                    otFinalizada,
                    alert,
                    submit,
                    finalizarOT
                };
            }
        }).mount('#finalizarOT');
    </script>
@endpush
