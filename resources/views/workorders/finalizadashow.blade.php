@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">

    <h2 class="text-lg font-semibold mb-4">
        Orden de Trabajo Finalizada #{{ $ordenTrabajo->n_documento }}
    </h2>

    @php
        $perfil = (int) (auth()->user()->perfil_usuario_id ?? 0);
        $isAdmin = in_array($perfil, [1, 2, 6], true);
    @endphp

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
            <p><strong>Finalizada por:</strong> {{ optional($ordenTrabajo->UsuariosOT)->name ?? '—' }}</p>
        </div>

    </div>

    {{-- DESCRIPCIÓN --}}
    <div class="mb-6 text-sm">
        <h3 class="font-semibold mb-2">📝 Descripción / Novedades</h3>

        <div class="p-4 border rounded bg-white whitespace-pre-line min-h-[120px]">
            {{ $ordenTrabajo->installation_notes }}
        </div>
    </div>

    {{-- ================= RESUMEN FINANCIERO ================= --}}
    <div class="border rounded-lg p-5 bg-white mb-6 text-sm shadow-sm">

        <h3 class="font-semibold mb-4 text-base">
            💰 Resumen OT
        </h3>

        {{-- MANO DE OBRA --}}
        <div class="mb-6">
            <h4 class="font-semibold mb-2">Mano de Obra</h4>

            <table class="w-full text-xs border">
                <thead class="bg-zinc-100">
                    <tr>
                        <th class="px-2 py-2 text-left">Tipo</th>
                        <th class="px-2 py-2 text-center">Horas</th>
                        <th class="px-2 py-2 text-right">Valor Hora</th>
                        <th class="px-2 py-2 text-right">Total</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($manoObra as $m)
                        <tr class="border-t">
                            <td class="px-2 py-2">
                                {{ $m->tipo }} - {{ $m->nombre_instalador }}
                            </td>

                            <td class="px-2 py-2 text-center">
                                {{ number_format($m->horas, 2) }}
                            </td>

                            <td class="px-2 py-2 text-right">
                                @if($isAdmin)
                                    $ {{ number_format($m->valor_hora, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-2 py-2 text-right font-semibold">
                                @if($isAdmin)
                                    $ {{ number_format($m->total, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="border-t bg-zinc-50">
                        <td colspan="3" class="text-right px-2 py-2 font-semibold">
                            Total Mano de Obra
                        </td>
                        <td class="text-right px-2 py-2 font-bold">
                            @if($isAdmin)
                                $ {{ number_format($manoObraTotal, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- TOTALES GENERALES --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div class="border p-4 rounded bg-zinc-50">
                <p class="text-sm text-zinc-600">Total Pedido</p>
                <p class="text-xl font-bold">
                    @if($isAdmin)
                        $ {{ number_format($pedidoTotal, 0, ',', '.') }}
                    @else
                        —
                    @endif
                </p>
            </div>

            <div class="border p-4 rounded bg-zinc-50">
                <p class="text-sm text-zinc-600">Total Material Adicional</p>
                <p class="text-xl font-bold">
                    @if($isAdmin)
                        $ {{ number_format($solicitudTotal, 0, ',', '.') }}
                    @else
                        —
                    @endif
                </p>
            </div>

            <div class="border p-4 rounded bg-zinc-50 col-span-1 md:col-span-2">
                <p class="text-sm text-zinc-600">Utilidad</p>

                @if($isAdmin)
                    <p class="text-2xl font-bold 
                        {{ $utilidad >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        $ {{ number_format($utilidad, 0, ',', '.') }}
                    </p>
                @else
                    <p class="text-lg font-semibold text-zinc-500">
                        Información restringida
                    </p>
                @endif
            </div>

        </div>

    </div>

    

    <div class="flex justify-end gap-2">
        <a href="{{ route('orders.pending.list') }}" class="btn btn-secondary">
            Volver
        </a>
    </div>

</div>
@endsection
