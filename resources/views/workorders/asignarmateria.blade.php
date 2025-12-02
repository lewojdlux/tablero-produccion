
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
        $isAsesor = $perfil === 5;
    @endphp

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-lg border border-zinc-200 ">
        <table class="w-full text-xs leading-tight">
            <thead>
                <th class="px-2 py-1 font-medium">Consecutivo</th>
                <th class="px-2 py-1 font-medium">Asesor</th>
                <th class="px-2 py-1 font-medium">Instalador</th>
                <th class="px-2 py-1 font-medium">Cliente</th>
                <th class="px-2 py-1 font-medium">Estado</th>
                <th class="px-2 py-1 font-medium text-center">Acciones</th>
            </thead>
            <tbody class="[&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-zinc-50">
                @forelse($dataMatrial as $workOrder)
                    <tr class="border-b border-zinc-200 hover:bg-zinc-50">
                        <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->n_documento }}</td>
                        <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->vendedor }}</td>
                        <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->instalador?->nombre_instalador ?? '' }}
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            {{ $workOrder->tercero }}
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            @if ($workOrder->status === 'pending')
                                <span class="btn btn-sm btn-danger disabled" tabindex="-1">Pendiente</span>
                            @elseif ($workOrder->status === 'in_progress')
                                <span class="btn btn-sm btn-warning disabled" tabindex="-1">En progreso</span>
                            @elseif ($workOrder->status === 'completed')
                                <span class="btn btn-sm btn-success disabled" tabindex="-1">Completado</span>
                            @elseif ($workOrder->status === 'assigned')
                                <span class="btn btn-sm btn-warning disabled" tabindex="-1">Asignado</span>
                            @endif
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">{{ $workOrder->created_at?->format('d-m-Y') }}</td>
                        <td class="px-2 py-1">
                            @if ($isInstalador)
                                <div class="flex justify-center gap-2">
                                    <button wire:click="openEditWorkOrder({{ $workOrder->id_work_order }})"
                                        type="button" class="btn btn-outline-secondary btn-sm">
                                        Iniciar
                                    </button>

                                    <a href="{{ route('asignar.material.show', $workOrder->id_work_order) }}"
                                        type="button" class="btn btn-outline-primary btn-sm">
                                        Asignar Herramientas
                                    </a>

                                </div>
                            @endif


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

    <div class="pt-2 text-xs">{{ $dataMatrial->links() }}</div>





</div>

@endsection
