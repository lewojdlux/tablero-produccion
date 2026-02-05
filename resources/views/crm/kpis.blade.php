@extends('layouts.app')

@section('content')
<div id="crmKpiApp" v-cloak class="space-y-6">

    {{-- TÍTULO --}}
    <div>
        <h2 class="text-xl font-bold">Dashboard CRM</h2>
        <p class="text-sm text-zinc-500">
            Seguimiento real de oportunidades y disciplina comercial
        </p>
    </div>

    {{-- FILTROS --}}
    <div class="bg-white p-4 border rounded flex flex-wrap gap-3 items-end">

        <div>
            <label class="text-xs font-semibold">Desde</label>
            <input type="date" v-model="filters.start"
                   class="border px-2 h-9 text-xs rounded">
        </div>

        <div>
            <label class="text-xs font-semibold">Hasta</label>
            <input type="date" v-model="filters.end"
                   class="border px-2 h-9 text-xs rounded">
        </div>

        @if (in_array(auth()->user()->perfil_usuario_id, [1,2,9]))
        <div>
            <label class="text-xs font-semibold">Asesor</label>
            <input type="text" v-model="filters.asesor"
                   class="border px-2 h-9 text-xs rounded">
        </div>
        @endif

        <button @click="cargarKpis"
                class="bg-black text-white px-5 h-9 text-xs rounded font-semibold">
            Consultar
        </button>
    </div>

    {{-- KPIs --}}
    <div v-if="kpis" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <KpiCard title="Total oportunidades"
                 :value="kpis.resumen.total_oportunidades || 0"
                 color="blue"/>

        <KpiCard title="Abiertas"
                 :value="kpis.resumen.abiertas || 0"
                 color="orange"/>

        <KpiCard title="Abiertas con atraso"
                 :value="kpis.resumen.abiertas_con_atraso || 0"
                 color="red"/>

        <KpiCard title="Cerradas"
                 :value="kpis.resumen.cerradas || 0"
                 color="green"/>
    </div>

    {{-- CUELLOS DE BOTELLA --}}
    <div v-if="kpis" class="bg-white p-4 border rounded">
        <h3 class="font-bold text-sm mb-3">
            ¿Dónde se están quedando las oportunidades abiertas?
        </h3>

        <table class="w-full text-xs">
            <thead class="bg-zinc-100">
                <tr>
                    <th>Actividad</th>
                    <th>Oportunidades</th>
                    <th class="text-red-600">Con atraso</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="r in kpis.porActividad" :key="r.actividad">
                    <td>@{{ r.actividad }}</td>
                    <td>@{{ r.oportunidades }}</td>
                    <td class="text-red-600 font-bold">
                        @{{ r.atrasadas }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- DISCIPLINA POR ASESOR (GERENCIAL) --}}
    @if (in_array(auth()->user()->perfil_usuario_id, [1,2,9]))
    <div v-if="kpis" class="bg-white p-4 border rounded">
        <h3 class="font-bold text-sm mb-3">
            Disciplina de gestión por asesor
        </h3>

        <table class="w-full text-xs">
            <thead class="bg-zinc-100">
                <tr>
                    <th>Asesor</th>
                    <th>Total</th>
                    <th>Abiertas</th>
                    <th>Cerradas</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="a in kpis.porAsesor" :key="a.asesor">
                    <td>@{{ a.asesor }}</td>
                    <td>@{{ a.total }}</td>
                    <td class="text-orange-600">@{{ a.abiertas }}</td>
                    <td class="text-green-600">@{{ a.cerradas }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    {{-- ACTIVIDADES PENDIENTES --}}
    <div v-if="kpis?.pendientes?.length"
         class="bg-white p-4 border rounded">

        <h3 class="font-bold text-sm text-red-600 mb-3">
            Actividades pendientes críticas
        </h3>

        <table class="w-full text-xs">
            <thead class="bg-red-50">
                <tr>
                    <th>Oportunidad</th>
                    <th>Cliente</th>
                    <th>Asesor</th>
                    <th>Actividad</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in kpis.pendientes" :key="p.IdOportunidad">
                    <td>@{{ p.IdOportunidad }}</td>
                    <td>@{{ p.cliente }}</td>
                    <td>@{{ p.asesor }}</td>
                    <td>@{{ p.actividad }}</td>
                    <td class="text-red-600">@{{ p.fecha }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            kpis: null,
            filters: {
                start: null,
                end: null,
                asesor: ''
            }
        }
    },
    methods: {
        async cargarKpis() {
            const params = new URLSearchParams();

            if (this.filters.start)  params.append('start', this.filters.start);
            if (this.filters.end)    params.append('end', this.filters.end);
            if (this.filters.asesor) params.append('asesor', this.filters.asesor);

            const res = await fetch(
                `{{ route('portal-crm.seguimiento.kpis.data') }}?${params.toString()}`
            );

            const json = await res.json();
            if (json.success) this.kpis = json.data;
        }
    },
    mounted() {
        this.cargarKpis();
    }
})
.component('KpiCard', {
    props: ['title','value','color'],
    template: `
        <div class="bg-white border-l-4 rounded p-4 shadow"
             :class="'border-' + color + '-600'">
            <div class="text-xs text-zinc-500" v-text="title"></div>
            <div class="text-3xl font-bold"
                 :class="'text-' + color + '-600'"
                 v-text="value">
            </div>
        </div>
    `
})
.mount('#crmKpiApp');
</script>
@endpush
