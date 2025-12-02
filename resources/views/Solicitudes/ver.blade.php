@extends('layouts.app')

@section('content')
<div class="space-y-6 p-6 bg-zinc-900 min-h-screen text-zinc-100">

    {{-- 🔹 ENCABEZADO --}}
    <div class="bg-zinc-800 border border-zinc-700 rounded-2xl p-5 shadow-md">
        <h2 class="text-lg font-semibold mb-4 text-indigo-400">🧾 Detalle de la Solicitud</h2>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <div>
                <span class="font-medium text-zinc-400">Consecutivo:</span>
                <span class="block text-zinc-100">{{ $solicitud->n_solicitud ?? '—' }}</span>
            </div>
            <div>
                <span class="font-medium text-zinc-400">Instalador:</span>
                <span class="block">{{ $solicitud->instalador->nombre_instalador ?? '—' }}</span>
            </div>
            <div>
                <span class="font-medium text-zinc-400">Proveedor:</span>
                <span class="block">{{ $solicitud->proveedor->name_supplier ?? '—' }}</span>
            </div>
            <div>
                <span class="font-medium text-zinc-400">Estado:</span>
                <span class="block capitalize">
                    @if ($solicitud->status === 'queued')
                        <span class="px-2 py-0.5 text-xs bg-amber-500/20 text-amber-400 rounded">Pendiente</span>
                    @elseif ($solicitud->status === 'in_progress')
                        <span class="px-2 py-0.5 text-xs bg-blue-500/20 text-blue-400 rounded">En progreso</span>
                    @elseif ($solicitud->status === 'done')
                        <span class="px-2 py-0.5 text-xs bg-green-500/20 text-green-400 rounded">Completado</span>
                    @else
                        <span class="px-2 py-0.5 text-xs bg-zinc-600/40 text-zinc-300 rounded">{{ $solicitud->status ?? '—' }}</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="font-medium text-zinc-400">Fecha registro:</span>
                <span class="block">{{ $solicitud->fecha_registro ?? '—' }}</span>
            </div>
            <div>
                <span class="font-medium text-zinc-400">Registrado por:</span>
                <span class="block">{{ $solicitud->user_reg ?? '—' }}</span>
            </div>
        </div>
    </div>


    @if (session('success'))
    <div class="p-2 mb-2 text-sm bg-green-600/20 text-green-300 rounded">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="p-2 mb-2 text-sm bg-red-600/20 text-red-300 rounded">{{ session('error') }}</div>
    @endif

    @if (session('warning'))
        <div class="p-2 mb-2 text-sm bg-amber-600/20 text-amber-300 rounded">{{ session('warning') }}</div>
    @endif


    {{-- 🔹 FORMULARIO EXCEL --}}
    <div class="bg-zinc-800 border border-zinc-700 rounded-2xl p-5 shadow-md">
        <h3 class="text-sm font-semibold mb-3 text-indigo-400">📁 Cargar materiales desde Excel</h3>
        <form action="{{ route('solicitudes.importExcel', $solicitud->id_solicitud_material) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="flex flex-wrap items-center gap-3">
                <input type="file" name="archivo_excel" accept=".xls,.xlsx"
                       class="file:bg-zinc-700 file:text-zinc-200 file:border-0 file:rounded file:px-3 file:py-1.5
                              file:mr-3 text-sm text-zinc-300 border border-zinc-600 rounded-lg p-1.5 w-72
                              focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <button type="submit" class="px-4 py-1.5 text-sm rounded-lg text-white bg-dark hover:bg-indigo-700 transition">
                    Subir archivo
                </button>
            </div>
            <p class="text-[11px] text-zinc-500 mt-2">Formatos permitidos: .xls, .xlsx</p>
        </form>
    </div>

    {{-- 🔹 FORMULARIO MANUAL --}}
    <div class="bg-zinc-800 border border-zinc-700 rounded-2xl p-5 shadow-md">
        <h3 class="text-sm font-semibold mb-3 text-indigo-400">🛠️ Agregar material manualmente</h3>
        <form action="{{ route('solicitudes.storeMaterial', $solicitud->id_solicitud_material) }}" method="POST" class="space-y-4">
            @csrf

            <input type="hidden" name="solicitud_material_id" value="{{ $solicitud->id_solicitud_material }}">

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label for="codigo_material" class="block text-zinc-400 mb-1">Código material</label>
                    <input type="text" name="codigo_material" id="codigo_material"
                           class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-2 py-1.5 text-zinc-100 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>

                <div>
                    <label for="cantidad" class="block text-zinc-400 mb-1">Cantidad</label>
                    <input type="number" name="cantidad" id="cantidad"
                           class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-2 py-1.5 text-zinc-100 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>

                <div>
                    <label for="precio_unitario" class="block text-zinc-400 mb-1">Precio unitario</label>
                    <input type="number" step="0.01" name="precio_unitario" id="precio_unitario"
                           class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-2 py-1.5 text-zinc-100 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>

                <div>
                    <label for="total" class="block text-zinc-400 mb-1">Total</label>
                    <input type="number" step="0.01" name="total" id="total"
                           class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-2 py-1.5 text-zinc-100 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" readonly>
                </div>

                <div>
                    <label for="fecha_registro" class="block text-zinc-400 mb-1">Fecha registro</label>
                    <input type="date" name="fecha_registro" id="fecha_registro"
                           value="{{ now()->format('Y-m-d') }}"
                           class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-2 py-1.5 text-zinc-100 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label for="user_reg" class="block text-zinc-400 mb-1">Usuario registro</label>
                    <input type="text" name="user_reg" id="user_reg" value="{{ auth()->user()->email }}"
                           readonly class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-2 py-1.5 text-zinc-400 cursor-not-allowed">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="px-4 py-1.5 rounded-lg text-white bg-dark  transition">
                    Guardar material
                </button>
            </div>
        </form>
    </div>

</div>

{{-- Script para calcular el total --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cantidad = document.getElementById('cantidad');
    const precio = document.getElementById('precio_unitario');
    const total = document.getElementById('total');

    function calcularTotal() {
        total.value = (parseFloat(cantidad.value || 0) * parseFloat(precio.value || 0)).toFixed(2);
    }

    cantidad.addEventListener('input', calcularTotal);
    precio.addEventListener('input', calcularTotal);
});
</script>
@endsection
