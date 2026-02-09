@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">

    <h2 class="text-lg font-semibold mb-4">
        Orden de Trabajo Finalizada #{{ $ordenTrabajo->n_documento }}
    </h2>

    {{-- MENSAJES --}}
    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 border border-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- INFORMACIÓN GENERAL --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-sm">

        <div>
            <p><strong>Cliente:</strong> {{ $ordenTrabajo->tercero }}</p>
            <p><strong>Asesor:</strong> {{ $ordenTrabajo->vendedor }}</p>
            <p><strong>Pedido de venta:</strong> {{ $ordenTrabajo->pedido ?? '—' }}</p>
        </div>

        <div>
            <p><strong>Instalador:</strong> {{ optional($ordenTrabajo->instalador)->nombre_instalador }}</p>
            <p><strong>Estado:</strong>
                <span class="px-2 py-1 text-xs rounded bg-green-200 text-green-900">
                    Finalizada
                </span>
            </p>
            <p><strong>Finalizada por:</strong>  {{ optional($ordenTrabajo->UsuariosOT)->name ?? '—' }}</p>
        </div>

    </div>

    {{-- CONTROL DE TIEMPOS --}}
    <div class="border rounded-lg p-5 bg-zinc-50 mb-6 text-sm">

        <h3 class="font-semibold mb-2">⏱️ Control de Mano de Obra</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <p><strong>Inicio real:</strong></p>
                <p>{{ \Carbon\Carbon::parse($ordenTrabajo->started_at)->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <p><strong>Final real:</strong></p>
                <p>{{ \Carbon\Carbon::parse($ordenTrabajo->finished_at)->format('Y-m-d H:i') }}</p>
            </div>
        </div>

        <div class="mt-3">
            <p>
                <strong>Duración total:</strong>
                {{ intdiv($ordenTrabajo->duration_minutes, 60) }} h
                {{ $ordenTrabajo->duration_minutes % 60 }} min
            </p>
        </div>

    </div>

    {{-- DESCRIPCIÓN Y NOVEDADES --}}
    <div class="mb-6 text-sm">
        <h3 class="font-semibold mb-2">📝 Descripción / Novedades</h3>

        <div class="p-4 border rounded bg-white whitespace-pre-line min-h-[120px]">
            {{ $ordenTrabajo->installation_notes }}
        </div>
    </div>

    {{-- ACCIONES --}}
    <div class="flex justify-end gap-2">
        <a href="{{ route('ordenes.trabajo.asignados') }}"
           class="btn btn-secondary">
            Volver
        </a>
    </div>

</div>
@endsection
