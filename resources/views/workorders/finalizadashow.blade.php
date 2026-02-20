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
        <div class="border rounded-xl p-6 bg-white shadow-sm mb-6">

            <h3 class="font-semibold mb-5 text-base">
                💰 Resumen Financiero
            </h3>

            {{-- MANO DE OBRA --}}
            <div class="mb-6">
                <h4 class="font-semibold mb-2">Mano de Obra</h4>

                <table class="w-full text-xs border rounded overflow-hidden">
                    <thead class="bg-zinc-100">
                        <tr>
                            <th class="px-3 py-2 text-left">Tipo</th>
                            <th class="px-3 py-2 text-center">Horas</th>
                            <th class="px-3 py-2 text-right">Valor Hora</th>
                            <th class="px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($manoObra as $m)
                            <tr class="border-t">
                                <td class="px-3 py-2">
                                    {{ $m->tipo }} - {{ $m->nombre_instalador }}
                                </td>

                                <td class="px-3 py-2 text-center">
                                    {{ number_format($m->horas, 2) }}
                                </td>

                                <td class="px-3 py-2 text-right">
                                    @if ($isAdmin)
                                        $ {{ number_format($m->valor_hora, 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-3 py-2 text-right font-semibold">
                                    @if ($isAdmin)
                                        $ {{ number_format($m->total, 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="bg-zinc-50">
                        <tr>
                            <td colspan="3" class="text-right px-3 py-2 font-semibold">
                                Total Mano de Obra
                            </td>
                            <td class="text-right px-3 py-2 font-bold">
                                @if ($isAdmin)
                                    $ {{ number_format($manoObraTotal, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- MATERIALES -->
            @if ($isAdmin && $materiales->count())
                <div class="mb-6">
                    <h4 class="font-semibold mb-2">Material Adicional</h4>

                    <table class="w-full text-xs border rounded overflow-hidden">
                        <thead class="bg-zinc-100">
                            <tr>
                                <th class="px-3 py-2 text-left">Código</th>
                                <th class="px-3 py-2 text-left">Descripción</th>
                                <th class="px-3 py-2 text-center">Cant</th>
                                <th class="px-3 py-2 text-right">Costo</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($materiales as $mat)
                                <tr class="border-t">
                                    <td class="px-3 py-2">
                                        {{ $mat->material_id ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2">
                                        {{ $mat->descripcion_material ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2 text-center">
                                        {{ $mat->cantidad ?? 1 }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-semibold">
                                        $ {{ number_format($mat->ultimo_costo, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="bg-zinc-50">
                            <tr>
                                <td colspan="3" class="text-right px-3 py-2 font-semibold">
                                    Total Material
                                </td>
                                <td class="text-right px-3 py-2 font-bold">
                                    $ {{ number_format($solicitudTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

            <!-- SERVICIOS -->
            @if ($isAdmin && $servicios->count())
                <div class="mb-6">
                    <h4 class="font-semibold mb-2">Servicios Pedido</h4>

                    <table class="w-full text-xs border rounded overflow-hidden">
                        <thead class="bg-zinc-100">
                            <tr>
                                <th class="px-3 py-2 text-left">Código</th>
                                <th class="px-3 py-2 text-left">Descripción</th>
                                <th class="px-3 py-2 text-center">Cant</th>
                                <th class="px-3 py-2 text-right">Valor Unit</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($servicios as $s)
                                <tr class="border-t">
                                    <td class="px-3 py-2">{{ $s->codigo }}</td>
                                    <td class="px-3 py-2">{{ $s->descripcion }}</td>
                                    <td class="px-3 py-2 text-center">{{ $s->cantidad }}</td>
                                    <td class="px-3 py-2 text-right">
                                        $ {{ number_format($s->valor_unitario, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold">
                                        $ {{ number_format($s->total, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="bg-zinc-50">
                            <tr>
                                <td colspan="4" class="text-right px-3 py-2 font-semibold">
                                    Total Pedido Servicio
                                </td>
                                <td class="text-right px-3 py-2 font-bold">
                                    $ {{ number_format($pedidoTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif


            {{-- TARJETAS RESUMEN --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="border p-5 rounded-lg bg-zinc-50">
                    <p class="text-sm text-zinc-500">Total Pedido</p>
                    <p class="text-xl font-bold">
                        @if ($isAdmin)
                            $ {{ number_format($pedidoTotal, 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </p>
                </div>

                <div class="border p-5 rounded-lg bg-zinc-50">
                    <p class="text-sm text-zinc-500">Material Adicional</p>
                    <p class="text-xl font-bold">
                        @if ($isAdmin)
                            $ {{ number_format($solicitudTotal, 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </p>
                </div>

                <div class="border p-6 rounded-lg bg-white col-span-1 md:col-span-2 shadow-sm">
                    <p class="text-sm text-zinc-500">Utilidad</p>

                    @if ($isAdmin)
                        <p
                            class="text-2xl font-bold 
                    {{ $utilidad >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            $ {{ number_format($utilidad, 0, ',', '.') }}
                        </p>

                        <p class="text-sm mt-2 text-zinc-600">
                            Margen:
                            <span
                                class="font-semibold 
                        {{ $porcentajeUtilidad >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $porcentajeUtilidad }} %
                            </span>
                        </p>
                    @else
                        <p class="text-lg text-zinc-500">
                            Información restringida
                        </p>
                    @endif
                </div>

            </div>

        </div>



        <div class="flex justify-end gap-2">
            <a href="{{ route('ordenes.trabajo.asignadas') }}" class="btn btn-secondary">
                Volver
            </a>
        </div>

    </div>
@endsection
