@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-4 rounded shadow">

    <h2 class="text-lg font-semibold mb-4">
        Finalizar Orden de Trabajo #{{ $ordenTrabajo->n_documento }}
    </h2>

    {{-- Información base --}}
    <div class="mb-4 text-sm">
        <p><strong>Cliente:</strong> {{ $ordenTrabajo->tercero }}</p>
        <p><strong>Pedido de venta:</strong> {{ $ordenTrabajo->pedido ?? '—' }}</p>
        <p><strong>Instalador:</strong> {{ optional($ordenTrabajo->instalador)->nombre_instalador }}</p>
    </div>


    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 border border-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 border border-red-300 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-yellow-100 text-yellow-800 border border-yellow-300 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('workorders.finalizar', $ordenTrabajo->id_work_order) }}">
        @csrf

        {{-- FECHAS Y HORAS REALES --}}
        <div class="grid grid-cols-2 gap-4 mb-4">

            <div>
                <label class="text-sm font-medium">Fecha y hora inicio REAL</label>
                <input
                    type="datetime-local"
                    name="started_at"
                    class="form-control"
                    required
                    value="{{ optional($ordenTrabajo->started_at)->format('Y-m-d\TH:i') }}"
                >
            </div>

            <div>
                <label class="text-sm font-medium">Fecha y hora final REAL</label>
                <input
                    type="datetime-local"
                    name="finished_at"
                    class="form-control"
                    required
                >
            </div>

        </div>

        {{-- DESCRIPCIÓN / NOVEDADES --}}
        <div class="mb-4">
            <label class="text-sm font-medium">
                Descripción de la instalación / Novedades
            </label>
            <textarea
                name="installation_notes"
                class="form-control"
                rows="5"
                required
                placeholder="Describa la instalación, inconvenientes, accesos, tiempos muertos, etc."></textarea>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('ordenes.trabajo.asignados') }}"
               class="btn btn-secondary">
               Cancelar
            </a>

            <button type="submit" class="btn btn-success">
                Finalizar OT
            </button>
        </div>

    </form>
</div>
@endsection
