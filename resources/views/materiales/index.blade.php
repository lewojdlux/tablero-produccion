@extends('layouts.app')

@section('content')

<div id="app">

    <h3>Materiales</h3>

    <button @click="cargarMateriales">Cargar materiales</button>
    <a href="/exportar-productos" class="btn btn-success">
        Exportar Excel REAL
    </a>

    <table border="1" width="100%" v-if="materiales.length">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Ubicación</th>
                <th>Inventario</th>
                <th>Reservado</th>
                <th>Disponible</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="m in materiales" :key="m.codigo">
                <td>@{{ m.codigo }}</td>
                <td>@{{ m.nombre }}</td>
                <td>@{{ m.ubicacion }}</td>
                <td>@{{ Math.round(m.saldo_inventario) }}</td>
                <td>@{{ Math.round(m.saldo_reservado) }}</td>
                <td>@{{ Math.round(m.saldo_disponible) }}</td>
            </tr>
        </tbody>
    </table>

</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            materiales: []
        }
    },

    methods: {
        async cargarMateriales() {

            try {
                const resp = await fetch('/materiales-all');
                const json = await resp.json();

                console.log(json); // DEBUG

                if (json.success) {
                    this.materiales = json.data;
                }

            } catch (e) {
                console.error(e);
            }
        }
    }

}).mount('#app');
</script>
@endpush