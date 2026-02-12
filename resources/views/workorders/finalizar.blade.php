@extends('layouts.app')

@section('content')
<style>
    [v-cloak] { display: none; }
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xxl-10">

            <div class="card shadow-lg border-0">
                <div class="card-body p-5">

                    <h3 class="mb-3 fw-bold">
                        Finalizar Orden de Trabajo #{{ $ordenTrabajo->n_documento }}
                    </h3>

                    <div class="mb-4">
                        <p><strong>Cliente:</strong> {{ $ordenTrabajo->tercero }}</p>
                        <p><strong>Pedido de venta:</strong> {{ $ordenTrabajo->pedido ?? '—' }}</p>
                        <p><strong>Instalador principal:</strong> {{ optional($ordenTrabajo->instalador)->nombre_instalador }}</p>
                    </div>

                    <div id="finalizarOT"
                         v-cloak
                         data-orden-id="{{ $ordenTrabajo->id_work_order }}"
                         data-csrf="{{ csrf_token() }}"
                         data-post-url="{{ route('workorders.otjornada', $ordenTrabajo->id_work_order) }}"
                         data-get-url="{{ route('workorders.jornadas', $ordenTrabajo->id_work_order) }}"
                         data-finalizar-url="{{ route('workorders.finalizar', $ordenTrabajo->id_work_order) }}"
                         data-instaladores='@json($instaladores)'>

                        {{-- ALERTAS --}}
                        <div v-if="alert.show"
                             class="alert"
                             :class="{
                                'alert-success': alert.type === 'success',
                                'alert-danger': alert.type === 'error',
                                'alert-warning': alert.type === 'warning'
                             }">
                            @{{ alert.message }}
                        </div>

                        {{-- JORNADAS REGISTRADAS --}}
                        <div v-if="jornadasRegistradas.length" class="mb-5">
                            <h5 class="fw-bold mb-3">Jornadas registradas</h5>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
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
                                            <td class="fw-bold">@{{ j.horas_trabajadas }}</td>
                                            <td>@{{ j.observaciones }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- FORM NUEVA JORNADA --}}
                        <form @submit.prevent="submit" v-if="!otFinalizada">

                            <h5 class="fw-bold mb-4">Registrar nueva jornada</h5>

                            <div v-for="(j, index) in jornadas"
                                 :key="index"
                                 class="card shadow-sm mb-4">

                                <div class="card-body">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-primary mb-0">
                                            Jornada #@{{ index + 1 }}
                                        </h6>

                                        <button v-if="jornadas.length > 1"
                                                type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                @click="removeJornada(index)">
                                            Quitar
                                        </button>
                                    </div>

                                    <div class="row g-4">

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Fecha</label>
                                            <input type="date"
                                                   class="form-control"
                                                   v-model="j.fecha"
                                                   required>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Hora inicio</label>
                                            <input type="time"
                                                   class="form-control"
                                                   v-model="j.hora_inicio"
                                                   required>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Hora fin</label>
                                            <input type="time"
                                                   class="form-control"
                                                   v-model="j.hora_fin"
                                                   required>
                                        </div>

                                        {{-- INSTALADORES COMO CHECKBOXES --}}
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                Instaladores participantes
                                            </label>

                                            <div class="row">
                                                <div class="col-md-4 mb-3"
                                                     v-for="inst in instaladores"
                                                     :key="inst.id_instalador">

                                                    <div class="border rounded p-3 shadow-sm bg-light">

                                                        <div class="form-check">
                                                            <input class="form-check-input"
                                                                   type="checkbox"
                                                                   :value="inst.id_instalador"
                                                                   v-model="j.instaladores"
                                                                   :id="'inst_' + inst.id_instalador">

                                                            <label class="form-check-label fw-medium"
                                                                   :for="'inst_' + inst.id_instalador">
                                                                @{{ inst.nombre_instalador }}
                                                            </label>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Observaciones</label>
                                            <textarea class="form-control"
                                                      rows="3"
                                                      v-model="j.observaciones"
                                                      placeholder="Detalle del trabajo realizado"></textarea>
                                        </div>

                                    </div>

                                </div>
                            </div>

                            {{-- DESCRIPCIÓN GENERAL --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Descripción general / Novedades
                                </label>
                                <textarea class="form-control"
                                          rows="4"
                                          v-model="installationNotes"></textarea>
                            </div>

                            {{-- BOTONES --}}
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('ordenes.trabajo.asignados') }}"
                                   class="btn btn-outline-secondary">
                                    Cancelar
                                </a>

                                <button class="btn btn-success">
                                    Guardar jornada
                                </button>

                                <button type="button"
                                        class="btn btn-danger"
                                        @click="finalizarOT">
                                    Finalizar OT
                                </button>
                            </div>

                        </form>

                        <div v-else class="alert alert-success mt-4">
                            Esta orden de trabajo ya fue finalizada.
                        </div>

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
                    observaciones: '',
                    instaladores: []
                }]);

                const installationNotes = ref('');
                const otFinalizada = ref(false);
                const instaladores = ref(JSON.parse(el.dataset.instaladores));


                async function cargarJornadas() {
                    const res = await axios.get(getUrl);
                    jornadasRegistradas.value = res.data;
                }

                async function submit() {
                    try {
                        const res = await axios.post(
                            postUrl, {
                                jornadas: jornadas.value
                            }, {
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            }
                        );

                        await cargarJornadas();

                        jornadas.value = [{
                            fecha: '',
                            hora_inicio: '',
                            hora_fin: '',
                            observaciones: '',
                            instaladores: []
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
                            finalizarUrl, {
                                installation_notes: installationNotes.value
                            }, {
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            }
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
                    instaladores,
                    submit,
                    finalizarOT
                };
            }
        }).mount('#finalizarOT');
    </script>
@endpush
