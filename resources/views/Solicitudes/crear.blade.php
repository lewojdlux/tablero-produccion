@extends('layouts.app')

@section('content')
<style>
    .excel-drop {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        background: #f8fafc;
    }

    [v-cloak] {
        display: none;
    }

</style>

<div id="app" v-cloak class="space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">
            Cargar solicitud de material
        </h2>

        <span class="text-xs text-zinc-500">
            OT #{{ $ordenTrabajo->n_documento }}
        </span>
    </div>

    {{-- INFO BASE --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">

        <div class="border rounded p-3">
            <strong>Instalador</strong><br>
            {{ optional($ordenTrabajo->instalador)->nombre_instalador ?? '—' }}
        </div>

        <div class="border rounded p-3">
            <strong>Cliente</strong><br>
            {{ $ordenTrabajo->tercero }}
        </div>

        <div class="border rounded p-3">
            <strong>Estado OT</strong><br>
            <span class="px-2 py-0.5 rounded bg-zinc-100">
                {{ $ordenTrabajo->status }}
            </span>
        </div>

    </div>

    {{-- FORM --}}
    <form ref="form"
      action="{{ route('solicitudes.store') }}"
      method="POST"
      enctype="multipart/form-data"
      class="space-y-6"
      @submit.prevent="submitForm">

        @csrf

        {{-- IDs ocultos --}}
        <input type="hidden" name="orden_trabajo_id" value="{{ $ordenTrabajo->id_work_order }}">
        <input type="hidden" name="instalador_id" value="{{ $ordenTrabajo->instalador_id }}">

        {{-- OBSERVACIONES --}}
        <div>
            <label class="text-xs font-medium">Observaciones</label>
            <textarea name="observaciones"
                      rows="3"
                      class="mt-1 w-full border rounded px-2 py-1 text-xs"
                      placeholder="Observaciones generales de la solicitud…"></textarea>
        </div>

        {{-- EXCEL --}}
        <div>
            <label class="text-xs font-medium">Archivo Excel</label>

            <div class="excel-drop mt-2">
                    <input type="file"
                    name="archivo_excel"
                    accept=".xlsx,.xls"
                    @change="onFileChange"
                    class="text-xs">
                <p class="mt-2 text-[11px] text-zinc-500">
                    Columnas esperadas:<br>
                    <strong>
                        Código | Descripción | Cantidad | Precio sin IVA | IVA
                    </strong>
                </p>
            </div>
        </div>

        {{-- BOTONES --}}
        <div class="flex justify-end gap-2">
            <a href="{{ url()->previous() }}"
               class="px-4 py-2 text-xs border rounded hover:bg-zinc-50">
                Cancelar
            </a>

            <div v-if="error"
                class="text-xs text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">
                @{{ error }}
            </div>


            <button type="submit"
                    :disabled="enviando"
                    class="px-4 py-2 text-xs rounded text-white"
                    :class="enviando ? 'bg-zinc-400 cursor-not-allowed' : 'bg-black hover:bg-zinc-800'">

                <span v-if="!enviando">Guardar solicitud</span>
                <span v-else>Procesando…</span>

            </button>
        </div>

    </form>

</div>
@endsection



@push('scripts')
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const { createApp } = Vue;

    createApp({
        data() {
            return {
                archivo: null,
                enviando: false,
                error: null
            }
        },

        methods: {

            onFileChange(e) {
                this.archivo = e.target.files[0];
                this.error = null;
            },

            validarFormulario() {

                if (!this.archivo) {
                    this.error = 'Debe adjuntar un archivo Excel.';
                    return false;
                }

                const ext = this.archivo.name.split('.').pop().toLowerCase();
                if (!['xls', 'xlsx'].includes(ext)) {
                    this.error = 'El archivo debe ser Excel (.xls o .xlsx).';
                    return false;
                }

                return true;
            },

            submitForm() {

                if (this.enviando) return;

                if (!this.validarFormulario()) return;

                this.enviando = true;

                this.$refs.form.submit();
            }
        }
    }).mount('#app');

});
</script>
@endpush
