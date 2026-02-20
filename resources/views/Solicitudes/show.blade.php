@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center border-b pb-3">
        <div>
            <h2 class="text-xl font-semibold">
                Solicitud de Material
            </h2>
            <p class="text-xs text-zinc-500">
                ID #{{ $pedido->id_solicitud_material }}
            </p>
        </div>

        @php
            $statusColors = [
                'queued' => 'bg-yellow-100 text-yellow-800',
                'in_progress' => 'bg-blue-100 text-blue-800',
                'done' => 'bg-green-100 text-green-800',
                'cancelled' => 'bg-red-100 text-red-800'
            ];
        @endphp

        <span class="px-3 py-1 text-xs font-semibold rounded-full 
            {{ $statusColors[$pedido->status] ?? 'bg-gray-100 text-gray-800' }}">
            {{ strtoupper($pedido->status) }}
        </span>
    </div>


    {{-- TARJETA PRINCIPAL --}}
    <div class="bg-white shadow-sm border rounded-lg p-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

            <div>
                <p class="text-xs text-zinc-500">Consecutivo Compra</p>
                <p class="text-lg font-semibold">
                    {{ $pedido->consecutivo_compra ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs text-zinc-500">Registrado por</p>
                <p class="font-medium">
                    {{ optional($pedido->usuarioRegistro)->name ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs text-zinc-500">Fecha Registro</p>
                <p>
                    {{ $pedido->fecha_registro 
                        ? \Carbon\Carbon::parse($pedido->fecha_registro)->format('d/m/Y H:i') 
                        : '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs text-zinc-500">Última Actualización</p>
                <p>
                    {{ $pedido->fecha_modificacion 
                        ? \Carbon\Carbon::parse($pedido->fecha_modificacion)->format('d/m/Y H:i') 
                        : '—' }}
                </p>
            </div>

        </div>

    </div>


    {{-- BOTONES --}}
    <div class="flex justify-end gap-3">

        <a href="{{ route('ordenes.trabajo.asignadas') }}"
           class="px-4 py-2 text-xs border rounded hover:bg-zinc-50">
            Volver
        </a>

    </div>

</div>
@endsection
