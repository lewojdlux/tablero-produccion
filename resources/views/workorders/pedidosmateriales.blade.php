@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">
            Pedido de Material #{{ $pedido->id }}
        </h2>

        <a href="{{ route('ordenes.trabajo.asignados') }}"
           class="text-xs text-indigo-600 hover:underline">
            ← Volver
        </a>
    </div>

    {{-- INFO GENERAL --}}
    <div class="grid grid-cols-2 gap-4 bg-white p-4 rounded border">
        <div>
            <p class="text-xs text-zinc-500">Documento</p>
            <p class="font-medium">{{ $pedido->n_documento }}</p>
        </div>

        <div>
            <p class="text-xs text-zinc-500">Instalador</p>
            <p class="font-medium">
                {{ optional($pedido->instalador)->nombre_instalador ?? '—' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500">Estado</p>
            <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                {{ strtoupper($pedido->estado) }}
            </span>
        </div>

        @php
            use Carbon\Carbon;
        @endphp

        <p class="font-medium">
            {{ $pedido->fecha_registro
                ? Carbon::parse($pedido->fecha_registro)->format('d/m/Y H:i:s')
                : '—'
            }}
        </p>

        @if($pedido->observacion)
        <div class="col-span-2">
            <p class="text-xs text-zinc-500">Observación</p>
            <p class="text-sm">{{ $pedido->observacion }}</p>
        </div>
        @endif
    </div>

    {{-- ITEMS --}}
    <div class="bg-white rounded border">
        <div class="px-4 py-2 border-b font-semibold text-sm">
            Material solicitado
        </div>

        <table class="w-full text-xs">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-3 py-2 text-left">Producto</th>
                    <th class="px-3 py-2 text-center">Descripción</th>
                    <th class="px-3 py-2 text-center">Cantidad</th>
                </tr>
            </thead>

            <tbody>
                @forelse($pedido->items as $item)
                    <tr class="border-t">
                        <td class="px-3 py-2">
                            {{ $item->producto_nombre ?? $item->codigo_material }}
                        </td>

                        <td class="px-3 py-2 text-center">
                            {{ $item->descripcion_material }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            {{ $item->cantidad }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-3 py-4 text-center text-zinc-500">
                            No hay materiales asociados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
