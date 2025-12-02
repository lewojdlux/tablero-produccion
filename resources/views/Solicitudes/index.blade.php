
@extends('layouts.app')

@section('content')
<div class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Orden de trabajo </h2>

    </div>

    {{-- Filtros --}}
    <details class="rounded border border-zinc-200 bg-zinc-50 p-2 pb-3 mb-3" open>
        <summary class="cursor-pointer text-[11px] text-zinc-700 select-none leading-none">Filtros</summary>
        <div class="mt-1.5 flex flex-wrap items-center gap-1 ">
            <input type="text" placeholder="Buscar por nombre / email / usuario" wire:model.live.debounce.400ms="search"
                class="h-8 w-[26rem] max-w-full rounded border border-zinc-300 px-2 text-[11px]
                          focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />

            <select wire:model.live="perPage"
                class="h-8 w-[5.5rem] rounded border border-zinc-300 px-2 text-[11px] bg-white
                           focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option>10</option>
                <option>25</option>
                <option>50</option>
            </select>
        </div>
    </details>

    @php
        $perfil = (int) (auth()->user()->perfil_usuario_id ?? 0);
        $isAdmin = in_array($perfil, [1, 2], true);
        $isInstalador = $perfil === 6;
    @endphp

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-lg border border-zinc-200 ">
        <table class="w-full text-xs leading-tight">
            <thead>
                <th class="px-2 py-1 font-medium">Consecutivo</th>
                <th class="px-2 py-1 font-medium">Instalador</th>
                <th class="px-2 py-1 font-medium">Proveedor</th>
                <th class="px-2 py-1 font-medium">Estado</th>
                <th class="px-2 py-1 font-medium text-center">Acciones</th>
            </thead>
            <tbody class="[&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-zinc-50">
                @forelse($dataSolicitudes as $solicitud)
                    <tr class="border-b border-zinc-200 hover:bg-zinc-50">
                        <td class="px-2 py-1 whitespace-nowrap">{{ $solicitud->n_solicitud }}</td>
                        <td class="px-2 py-1 whitespace-nowrap">{{ $solicitud->instalador?->nombre_instalador ?? '' }}
                        <td class="px-2 py-1 whitespace-nowrap">{{ $solicitud->proveedor->name_supplier ?? '' }}</td>

                        <td class="px-2 py-1 whitespace-nowrap">
                            @if ($solicitud->status === 'queued')
                                <span class="btn btn-sm btn-danger disabled" tabindex="-1">Pendiente</span>
                            @elseif ($solicitud->status === 'in_progress')
                                <span class="btn btn-sm btn-warning disabled" tabindex="-1">En progreso</span>
                            @elseif ($solicitud->status === 'done')
                                <span class="btn btn-sm btn-success disabled" tabindex="-1">Completado</span>

                            @endif
                        </td>

                        <td class="px-2 py-1 whitespace-nowrap">
                            <a href="{{ route('solicitudes.show', $solicitud->id_solicitud_material) }}" class="btn btn-sm btn-dark ">
                                Ver
                            </a>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-2 py-6 text-center text-zinc-500">Sin resultados…</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-2 text-xs">{{ $dataSolicitudes->links() }}</div>





</div>

@endsection
