@extends('layouts.app')

@section('content')
    <style>
        [v-cloak] {
            display: none;
        }
    </style>

    <div class="px-2 py-2">
        <h3 class="mb-3 fw-bold">
            Actividades de la OT #{{ $ordenTrabajo->n_documento }}
        </h3>

        <div class="mb-4">
            <p><strong>Cliente:</strong> {{ $ordenTrabajo->tercero }}</p>
            <p><strong>Pedido de venta:</strong> {{ $ordenTrabajo->pedido ?? '—' }}</p>
            <p><strong>Instalador principal:</strong>
                {{ optional($ordenTrabajo->instalador)->nombre_instalador }}</p>
        </div>

        <div id="finalizarOT" v-cloak data-orden-id="{{ $ordenTrabajo->id_work_order }}" data-csrf="{{ csrf_token() }}"
            data-post-url="{{ route('workorders.otjornada', $ordenTrabajo->id_work_order) }}"
            data-get-url="{{ route('workorders.jornadas', $ordenTrabajo->id_work_order) }}"
            data-finalizar-url="{{ route('workorders.finalizar', $ordenTrabajo->id_work_order) }}"
            data-instaladores='@json($instaladores)' data-principal='{{ $ordenTrabajo->instalador_id }}'
            data-acompanantes='@json($ordenTrabajo->acompanantes->pluck('id_instalador'))' data-perfil="{{ auth()->user()->perfil_usuario_id }}"
            data-ot-status="{{ $ordenTrabajo->status }}" data-fecha-inicio="{{ $ordenTrabajo->fecha_programada }}"
            data-fecha-fin="{{ $ordenTrabajo->fecha_programada_fin }}">

            {{-- ALERTAS --}}
            <div v-if="alert.show" class="alert alert-dismissible fade show"
                :class="{
                    'alert-success': alert.type === 'success',
                    'alert-danger': alert.type === 'error',
                    'alert-warning': alert.type === 'warning'
                }"
                role="alert">

                @{{ alert.message }}

                <button type="button" class="btn-close" @click="alert.show = false">
                </button>
            </div>

            {{-- JORNADAS REGISTRADAS --}}
            <div v-if="jornadasRegistradas.length" class="mb-5">
                <h5 class="fw-bold mb-3">Jornadas registradas</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Fecha</th>
                                <th>Hora inicio</th>
                                <th>Hora fin</th>
                                <th>Horas</th>
                                <th>Observaciones</th>
                                <th v-if="otAbierta">Guardar</th>
                                <th v-if="esAdmin">Eliminar</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(j, i) in jornadasRegistradas" :key="j.id" class="text-center">

                                <td>@{{ j.fecha }}</td>

                                <td>
                                    <input type="time" class="form-control form-control-sm" v-model="j.hora_inicio"
                                        :disabled="!otAbierta">
                                </td>

                                <td>
                                    <input type="time" class="form-control form-control-sm" v-model="j.hora_fin"
                                        :disabled="!otAbierta">
                                </td>

                                <td class="fw-bold">
                                    @{{ j.horas_trabajadas }}
                                </td>

                                <td>
                                    @{{ j.observaciones }}
                                </td>

                                <!-- BOTÓN GUARDAR SOLO SI OT ABIERTA -->
                                <td v-if="otAbierta">
                                    <button class="btn btn-sm btn-outline-primary" @click="actualizarJornada(j)">
                                        Guardar
                                    </button>
                                </td>

                                <!-- BOTÓN ELIMINAR SOLO ADMIN -->
                                <td v-if="esAdmin">
                                    <button class="btn btn-sm btn-outline-danger" @click="eliminarJornada(j.id)">
                                        Eliminar
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- FORM NUEVA JORNADA --}}
            <form @submit.prevent="submit"
                v-if="mostrarFormularioJornada 
                && !(fechaInicioProgramada === fechaFinProgramada && jornadasRegistradas.length)">

                <h5 class="fw-bold mb-4">Registrar nueva jornada</h5>

                <div v-for="(j, index) in jornadas" :key="index" class="card shadow-sm mb-4">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-primary mb-0">
                                Jornada #@{{ jornadasRegistradas.length + index + 1 }}
                            </h6>

                            <button v-if="jornadas.length > 1" type="button" class="btn btn-outline-danger btn-sm"
                                @click="removeJornada(index)">
                                Quitar
                            </button>
                        </div>

                        <div class="row g-4">

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha</label>
                                <input type="date" class="form-control" v-model="j.fecha" :min="fechaInicioProgramada"
                                    :max="fechaFinProgramada" required readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Hora inicio</label>
                                <input type="time" class="form-control" v-model="j.hora_inicio" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Hora fin</label>
                                <input type="time" class="form-control" v-model="j.hora_fin">
                            </div>

                            {{-- INSTALADORES COMO CHECKBOXES --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Instaladores participantes
                                </label>

                                <div class="row">
                                    <div class="col-md-4 mb-3" v-for="inst in instaladores" :key="inst.id_instalador">

                                        <div class="border rounded p-3 shadow-sm bg-light">

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" :value="inst.id_instalador"
                                                    v-model="j.instaladores" :id="'inst_' + inst.id_instalador"
                                                    :disabled="inst.id_instalador == principal">

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
                                <textarea class="form-control" rows="3" v-model="j.observaciones" placeholder="Detalle del trabajo realizado"></textarea>
                            </div>

                        </div>

                    </div>
                </div>

            </form>



            <!-- DESCRIPCIÓN GENERAL (SIEMPRE VISIBLE SI OT ABIERTA) -->
            <div v-if="otAbierta" class="mt-4">
                <label class="form-label fw-semibold">
                    Descripción general / Novedades
                </label>
                <textarea class="form-control" rows="4" v-model="installationNotes"></textarea>
            </div>

            <!-- ACCIONES GENERALES -->
            <div v-if="otAbierta" class="d-flex justify-content-between mt-4">

                <!-- IZQUIERDA -->
                <a href="{{ route('ordenes.trabajo.asignados') }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>

                <!-- DERECHA -->
                <div class="d-flex gap-2">

                    <!-- Guardar jornada SOLO si puede -->
                    <button v-if="puedeRegistrarMasJornadas" class="btn btn-success" @click="submit">
                        Guardar jornada
                    </button>


                    <button type="button" class="btn btn-warning" @click="mostrarModalNovedad = true">
                        Registrar Novedad
                    </button>


                    <!-- Finalizar siempre visible -->
                    <button type="button" class="btn btn-danger" @click="finalizarOT">
                        Finalizar OT
                    </button>

                </div>
            </div>

            <div v-if="otAbierta && !puedeRegistrarMasJornadas" class="alert alert-info mt-3">
                Ya se alcanzó la fecha final programada. No se pueden registrar más jornadas.
            </div>


            <!-- MODAL JORNADA PENDIENTE -->
            <div v-if="mostrarModalPendiente" class="modal fade show d-block" style="background: rgba(0,0,0,0.7);">

                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">

                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                Jornada pendiente sin finalizar
                            </h5>
                        </div>

                        <div class="modal-body">

                            <p>
                                No finalizaste la jornada del día
                                <strong>@{{ jornadaPendiente.fecha }}</strong>.
                            </p>

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Ingrese hora final
                                </label>

                                <input type="time" class="form-control" v-model="jornadaPendiente.hora_fin">
                            </div>

                        </div>

                        <div class="modal-footer">

                            <button class="btn btn-danger" @click="guardarHoraPendiente">
                                Guardar y continuar
                            </button>

                        </div>

                    </div>
                </div>
            </div>


            <div v-if="mostrarModalNovedad" class="modal fade show d-block" style="background: rgba(0,0,0,0.7);">

                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content shadow-lg border-0">

                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">Registrar Novedad</h5>
                        </div>

                        <div class="modal-body">

                            <div class="mb-3">
                                <label>Fecha afectada</label>
                                <input type="date" class="form-control" v-model="formNovedad.fecha_afectada">
                            </div>

                            <div class="mb-3">
                                <label>Tipo</label>
                                <select class="form-control" v-model="formNovedad.tipo_novedad">
                                    <option value="cliente_no_disponible">Cliente no disponible</option>
                                    <option value="clima">Clima</option>
                                    <option value="material_pendiente">Material pendiente</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Observación</label>
                                <textarea class="form-control" v-model="formNovedad.observacion"></textarea>
                            </div>

                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" v-model="formNovedad.reprogramar">
                                <label class="form-check-label">Reprogramar</label>
                            </div>

                            <div v-if="formNovedad.reprogramar">
                                <label>Nueva fecha</label>
                                <input type="date" class="form-control" v-model="formNovedad.nueva_fecha">
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary" @click="mostrarModalNovedad = false">
                                Cancelar
                            </button>

                            <button class="btn btn-warning" @click="guardarNovedad">
                                Guardar
                            </button>
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
                onMounted,
                computed
            } = Vue;

            createApp({
                setup() {

                    const el = document.getElementById('finalizarOT');

                    const csrfToken = el.dataset.csrf;
                    const postUrl = el.dataset.postUrl;
                    const getUrl = el.dataset.getUrl;
                    const finalizarUrl = el.dataset.finalizarUrl;
                    const principal = Number(el.dataset.principal);
                    const acompanantes = JSON.parse(el.dataset.acompanantes || '[]');
                    const perfil = Number(el.dataset.perfil);
                    const otStatus = el.dataset.otStatus;
                    const fechaPendiente = ref(null);
                    const fechaInicioProgramada = el.dataset.fechaInicio;
                    const fechaFinProgramada = ref(el.dataset.fechaFin);;
                    const jornadasRegistradas = ref([]);
                    const jornadas = ref([]);

                    const mostrarModalNovedad = ref(false);

                    const formNovedad = ref({
                        fecha_afectada: '',
                        tipo_novedad: '',
                        observacion: '',
                        reprogramar: false,
                        nueva_fecha: ''
                    });

                    const puedeRegistrarMasJornadas = computed(() => {

                        if (!fechaFinProgramada.value) return true;

                        const existePendiente = jornadasRegistradas.value.some(j =>
                            !j.hora_fin || j.hora_fin === ''
                        );

                        if (existePendiente) {
                            return false;
                        }

                        const ordenadas = [...jornadasRegistradas.value]
                            .sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

                        const ultimaFecha = ordenadas.length
                            ? ordenadas[0].fecha
                            : fechaInicioProgramada;

                        return ultimaFecha <= fechaFinProgramada.value;
                    });


                    const mostrarFormularioJornada = computed(() => {

                        if (!otAbierta) return false;

                        // Si hay fecha pendiente por novedad, sí se permite
                        if (fechaPendiente.value) return true;

                        // Si la OT es de un solo día
                        if (fechaInicioProgramada === fechaFinProgramada.value) {

                            const existeJornadaEseDia = jornadasRegistradas.value.some(
                                j => j.fecha === fechaInicioProgramada
                            );

                            // Si ya hay jornada ese día -> NO mostrar formulario
                            return !existeJornadaEseDia;
                        }

                        // Si aún no hay jornadas registradas -> permitir primera
                        if (!jornadasRegistradas.value.length) return true;

                        // OT de varios días
                        const ordenadas = [...jornadasRegistradas.value]
                            .sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

                        const ultimaFecha = ordenadas[0].fecha;

                        return ultimaFecha < fechaFinProgramada.value;

                    });


                    function calcularFechaSiguiente() {
                        // Si no hay jornadas, usar fecha programada inicio
                        if (!jornadasRegistradas.value.length) {
                            return fechaInicioProgramada;
                        }

                        const ordenadas = [...jornadasRegistradas.value]
                            .sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

                        const ultimaFecha = new Date(ordenadas[0].fecha);

                        ultimaFecha.setDate(ultimaFecha.getDate() + 1);

                        const siguiente = ultimaFecha.toISOString().split('T')[0];

                        if (fechaFinProgramada.value && siguiente > fechaFinProgramada.value) {
                            return null;
                        }

                        return siguiente;
                    }

                    const mostrarModalPendiente = ref(false);
                    const jornadaPendiente = ref(null);

                    const esAdmin = perfil === 1 || perfil === 2;
                    const otAbierta = otStatus !== 'completed';


                    const alert = ref({
                        show: false,
                        type: 'success', // success | error | warning
                        message: ''
                    });


                    const installationNotes = ref('');
                    // const otFinalizada = ref(false);
                    const otFinalizada = ref(otStatus === 'completed');
                    const instaladores = ref(JSON.parse(el.dataset.instaladores));


                    async function cargarJornadas() {
                        const res = await axios.get(getUrl);
                        jornadasRegistradas.value = res.data;
                    }

                    async function verificarJornadaPendiente() {

                        const res = await axios.get(
                            `/ordenes-trabajo/${el.dataset.ordenId}/jornada-pendiente`
                        );

                        if (res.data.pendiente) {
                            jornadaPendiente.value = res.data.jornada;
                            mostrarModalPendiente.value = true;
                        }
                    }

                    onMounted(async () => {
                        await cargarJornadas();
                        await verificarFechasPendientes();

                        if (!mostrarFormularioJornada.value) {
                            jornadas.value = [];
                            return;
                        }

                        if (fechaPendiente.value) {
                            jornadas.value = [{
                                fecha: fechaPendiente.value,
                                hora_inicio: '',
                                hora_fin: '',
                                observaciones: '',
                                instaladores: [principal, ...acompanantes]
                            }];
                            return;
                        }

                        const siguiente = calcularFechaSiguiente();

                        if (!siguiente) {
                            jornadas.value = [];
                            return;
                        }

                        jornadas.value = [{
                            fecha: siguiente,
                            hora_inicio: '',
                            hora_fin: '',
                            observaciones: '',
                            instaladores: [principal, ...acompanantes]
                        }];

                        await verificarJornadaPendiente();
                    });


                    function showAlert(type, message) {
                        alert.value = {
                            show: true,
                            type: type,
                            message: message
                        };

                        setTimeout(() => {
                            alert.value.show = false;
                        }, 3000);
                    }

                    async function submit() {


                        for (const j of jornadas.value) {

                            if (j.fecha < fechaInicioProgramada) {
                                showAlert('warning', 'La fecha no puede ser menor a la fecha programada.');
                                return;
                            }

                            if (j.fecha > fechaFinProgramada.value) {
                                showAlert('warning', 'La fecha no puede superar la fecha final programada.');
                                return;
                            }
                        }


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
                            await verificarJornadaPendiente();


                            const siguiente = calcularFechaSiguiente();

                            if (!siguiente) {
                                jornadas.value = [];
                                return;
                            }

                            jornadas.value = [{
                                fecha: siguiente,
                                hora_inicio: '',
                                hora_fin: '',
                                observaciones: '',
                                instaladores: [principal, ...acompanantes]
                            }];


                            showAlert('success', res.data.message);

                        } catch (e) {

                            showAlert('error', e.response?.data?.message || 'Error al guardar la jornada');
                        }
                    }

                    async function finalizarOT() {
                        /*if (!installationNotes.value) {

                            showAlert('warning', 'Debe ingresar la descripción general.');
                            return;
                        }*/

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

                            showAlert('success', res.data.message);

                            otFinalizada.value = true;

                            setTimeout(() => {
                                window.location.href = "{{ route('ordenes.trabajo.asignados') }}";
                            }, 1500);

                        } catch (e) {


                            showAlert('error', e.response?.data?.message || 'Error al finalizar la OT');
                        }
                    }


                    async function actualizarJornada(j) {


                        if (j.hora_fin && j.hora_inicio >= j.hora_fin) {

                            showAlert('warning', 'La hora final debe ser mayor que la inicial.');
                            return;
                        }


                        try {
                            const res = await axios.put(
                                `/ordenes-trabajo/jornadas/${j.id}`, {
                                    hora_inicio: j.hora_inicio,
                                    hora_fin: j.hora_fin
                                }, {
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                }
                            );
                            showAlert('success', res.data.message);


                            await cargarJornadas();

                        } catch (e) {
                            showAlert('error', e.response?.data?.message || 'Error al actualizar la jornada');

                        }
                    }


                    async function eliminarJornada(id) {

                        if (!confirm('¿Eliminar esta jornada?')) return;

                        try {
                            const res = await axios.delete(
                                `/ordenes-trabajo/jornadas/${id}`, {
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                }
                            );

                            showAlert('success', res.data.message);


                            await cargarJornadas();

                        } catch (e) {
                            showAlert('error', e.response?.data?.message || 'Error al eliminar la jornada');
                        }
                    }


                    async function guardarHoraPendiente() {

                        if (!jornadaPendiente.value.hora_fin) {
                            showAlert('warning', 'Debe ingresar hora final.');
                            return;
                        }

                        if (jornadaPendiente.value.hora_fin <= jornadaPendiente.value.hora_inicio) {
                            showAlert('warning', 'La hora final debe ser mayor.');
                            return;
                        }

                        try {

                            await axios.put(
                                `/ordenes-trabajo/jornadas/${jornadaPendiente.value.id}`, {
                                    hora_inicio: jornadaPendiente.value.hora_inicio,
                                    hora_fin: jornadaPendiente.value.hora_fin
                                }, {
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                }
                            );

                            mostrarModalPendiente.value = false;
                            jornadaPendiente.value = null;

                            await cargarJornadas();

                            showAlert('success', 'Jornada completada correctamente.');

                        } catch (e) {
                            showAlert('error', 'Error al guardar.');
                        }
                    }


                    async function guardarNovedad() {

                        if (!formNovedad.value.fecha_afectada) {
                            showAlert('warning', 'Debe seleccionar la fecha afectada.');
                            return;
                        }

                        if (!formNovedad.value.tipo_novedad) {
                            showAlert('warning', 'Debe seleccionar el tipo de novedad.');
                            return;
                        }

                        try {

                            const res = await axios.post(
                                `/ordenes-trabajo/${el.dataset.ordenId}/novedad`,
                                formNovedad.value,
                                {
                                    headers: { 'X-CSRF-TOKEN': csrfToken }
                                }
                            );

                            showAlert('success', res.data.message);

                            mostrarModalNovedad.value = false;

                            await cargarJornadas();
                            await verificarFechasPendientes();

                            // Resetear formulario
                            formNovedad.value = {
                                fecha_afectada: '',
                                tipo_novedad: '',
                                observacion: '',
                                reprogramar: false,
                                nueva_fecha: ''
                            };

                        } catch (e) {

                            showAlert(
                                'error',
                                e.response?.data?.message || 'Error registrando novedad'
                            );
                        }
                    }

                    async function verificarFechasPendientes() {
                        const res = await axios.get(
                            `/ordenes-trabajo/${el.dataset.ordenId}/fechas-pendientes`
                        );

                        if (res.data.pendiente) {
                            fechaPendiente.value = res.data.fecha_sugerida;

                            jornadas.value = [{
                                fecha: fechaPendiente.value,
                                hora_inicio: '',
                                hora_fin: '',
                                observaciones: '',
                                instaladores: [principal, ...acompanantes]
                            }];
                        }
                    }




                    return {
                        jornadasRegistradas,
                        jornadas,
                        installationNotes,
                        otFinalizada,
                        alert,
                        instaladores,
                        principal,
                        submit,
                        finalizarOT,
                        esAdmin,
                        otAbierta,
                        actualizarJornada,
                        eliminarJornada,
                        mostrarModalPendiente,
                        jornadaPendiente,
                        guardarHoraPendiente,
                        fechaInicioProgramada,
                        fechaFinProgramada,
                        puedeRegistrarMasJornadas,
                        mostrarModalNovedad,
                        formNovedad,
                        guardarNovedad,
                        verificarFechasPendientes,
                        fechaPendiente,
                        mostrarFormularioJornada
                    };
                }
            }).mount('#finalizarOT');
        </script>
    @endpush
