@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">
            Orden de Trabajo #{{ $ordenTrabajo->n_documento }}
        </h2>

        <a href="{{ route('ordenes.trabajo.asignadas') }}"
           class="text-xs text-indigo-600 hover:underline">
            ← Volver
        </a>
    </div>

    {{-- INFO GENERAL --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-4 rounded border">

        <div>
            <p class="text-xs text-zinc-500">Instalador</p>
            <p class="font-medium">
                {{ $ordenTrabajo->pedidosMateriales->first()->instalador->nombre_instalador ?? '—' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500">Estado OT</p>
            <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                {{ strtoupper($ordenTrabajo->status) }}
            </span>
        </div>
    </div>

    {{-- PEDIDOS DE MATERIAL --}}
    @forelse($ordenTrabajo->pedidosMateriales as $pedido)
        <div class="bg-white rounded border">

            <div class="px-4 py-2 border-b font-semibold text-sm">
                Pedido #{{ $pedido->id_pedido_material }}
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-zinc-500">Estado</p>
                    <p>{{ strtoupper($pedido->status) }}</p>
                </div>

                <div>
                    <p class="text-xs text-zinc-500">Fecha solicitud</p>
                    <p>
                        {{ $pedido->fecha_registro
                            ? \Carbon\Carbon::parse($pedido->fecha_registro)->format('d/m/Y H:i')
                            : '—'
                        }}
                    </p>
                </div>

                @if($pedido->observaciones)
                    <div class="md:col-span-2">
                        <p class="text-xs text-zinc-500">Observaciones</p>
                        <p>{{ $pedido->observaciones }}</p>
                    </div>
                @endif
            </div>

            {{-- ITEMS --}}
            <table class="w-full text-xs border-t">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Código</th>
                        <th class="px-3 py-2 text-left">Descripción</th>
                        <th class="px-3 py-2 text-center">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pedido->items as $item)
                        <tr class="border-t">
                            <td class="px-3 py-2">{{ $item->codigo_material }}</td>
                            <td class="px-3 py-2">{{ $item->descripcion_material }}</td>
                            <td class="px-3 py-2 text-center">{{ $item->cantidad }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-zinc-500">
                                Sin materiales
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    @empty
        <p class="text-zinc-500">
            Esta orden de trabajo no tiene pedidos de material.
        </p>
    @endforelse

</div>
@endsection
