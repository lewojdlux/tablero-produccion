@extends('layouts.app')

@section('content')

<div id="app">

    <h3>Materiales</h3>

    <button @click="cargarMateriales">Cargar materiales</button>
    <button @click="exportarExcel">Exportar Excel</button>

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
        },

        exportarExcel() {

            if (!this.materiales.length) {
                alert('No hay datos para exportar');
                return;
            }

            // 2 COLUMNAS: codigo, saldo_disponible
            let contenido = `
                <table>
                    <tbody>
            `;

            this.materiales.forEach(m => {
                contenido += `
                    <tr>
                        <td>${m.codigo}</td>
                        <td>${Math.round(m.saldo_disponible)}</td>
                    </tr>
                `;
            });

            contenido += `
                    </tbody>
                </table>
            `;

            const archivo = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office"
                    xmlns:x="urn:schemas-microsoft-com:office:excel"
                    xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="UTF-8">
                </head>
                <body>
                    ${contenido}
                </body>
                </html>
            `;

            const blob = new Blob([archivo], {
                type: 'application/vnd.ms-excel;charset=utf-8;'
            });

            const url = URL.createObjectURL(blob);

            const link = document.createElement("a");
            link.href = url;
            link.download = "productos_importar.xls";

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            URL.revokeObjectURL(url);
        }
    }

}).mount('#app');
</script>
@endpush