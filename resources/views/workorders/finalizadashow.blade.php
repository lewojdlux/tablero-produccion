@extends('layouts.app')

@section('content')



    @php
        $perfil = (int) (auth()->user()->perfil_usuario_id ?? 0);
        $isAdmin = in_array($perfil, [1, 2, 6], true);
    @endphp

    {{-- HEADER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">

            <div>
                <h4 class="mb-1">
                    Orden de Trabajo #{{ $ordenTrabajo->n_documento }}
                </h4>

                <small class="text-muted">
                    Cliente: {{ $ordenTrabajo->tercero ?? '-' }} |
                    Asesor: {{ $ordenTrabajo->vendedor ?? '-' }} |
                    Pedido: {{ $ordenTrabajo->pedido ?? '-' }}
                </small>
            </div>

            <span class="badge bg-success fs-6">
                Finalizada
            </span>

        </div>
    </div>


    {{-- DESCRIPCIÓN --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">
            Descripción / Novedades
        </div>
        <div class="card-body">
            {{ $ordenTrabajo->installation_notes ?? 'Sin observaciones' }}
        </div>
    </div>


    {{-- ================= RESUMEN FINANCIERO ================= --}}
    <div class="card shadow-sm mb-4">

        <div class="card-header fw-bold">
            Resumen Financiero
        </div>

        <div class="card-body">

            {{-- MANO DE OBRA --}}
            <div class="mb-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3">
                    Mano de Obra
                </h6>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th class="text-center">Horas</th>
                                <th class="text-end">Valor Hora</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($manoObra as $m)
                                <tr>
                                    <td>{{ $m->tipo }} - {{ $m->nombre_instalador }}</td>
                                    <td class="text-center">{{ number_format($m->horas ?? 0, 2) }}</td>
                                    <td class="text-end">$ {{ number_format($m->valor_hora ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">
                                        $ {{ number_format($m->total ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Sin registros
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">
                                    Total Mano de Obra
                                </th>
                                <th class="text-end">
                                    $ {{ number_format($manoObraTotal ?? 0, 0, ',', '.') }}
                                </th>
                            </tr>
                        </tfoot>

                    </table>
                </div>
            </div>


            {{-- MATERIAL ADICIONAL --}}
            @if ($isAdmin)
                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase text-muted mb-3">
                        Material Adicional
                    </h6>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle">

                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Cant</th>
                                    <th class="text-end">V. Unitario</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($materiales as $mat)
                                    <tr>
                                        <td>{{ $mat->material_id ?? '-' }}</td>
                                        <td>{{ $mat->descripcion_material ?? '-' }}</td>
                                        <td class="text-center">{{ $mat->cantidad ?? 0 }}</td>
                                        <td class="text-end">
                                            $ {{ number_format($mat->ultimo_costo ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end fw-semibold">
                                            $ {{ number_format($mat->total ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Sin materiales
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">
                                        Total Material
                                    </th>
                                    <th class="text-end">
                                        $ {{ number_format($solicitudTotal ?? 0, 0, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>

                        </table>
                    </div>
                </div>
            @endif


            {{-- SERVICIOS --}}
            @if ($isAdmin)
                @foreach ($serviciosPorDocumento as $documento => $grupo)
                    <div class="mb-4">
                        <h6 class="fw-bold text-uppercase text-muted mb-3">
                            PD {{ $documento }}
                        </h6>

                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">

                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Cant</th>
                                        <th class="text-end">Valor Unit</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($grupo['items'] as $s)
                                        <tr>
                                            <td>{{ $s->codigo }}</td>
                                            <td>{{ $s->descripcion }}</td>
                                            <td class="text-center">{{ $s->cantidad }}</td>
                                            <td class="text-end">
                                                $ {{ number_format($s->valor_unitario, 0, ',', '.') }}
                                            </td>
                                            <td class="text-end fw-semibold">
                                                $ {{ number_format($s->total, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">
                                            Subtotal PD {{ $documento }}
                                        </th>
                                        <th class="text-end">
                                            $ {{ number_format($grupo['total'], 0, ',', '.') }}
                                        </th>
                                    </tr>
                                </tfoot>

                            </table>
                        </div>
                    </div>
                @endforeach
            @endif


            {{-- KPI CARDS --}}
            <div class="card shadow-sm mt-4">

                <div class="card-body p-0">

                    <table class="table table-sm mb-0 align-middle">

                        <tbody>

                            <tr>
                                <th class="text-uppercase text-muted ps-4">
                                    Total Mano de Obra
                                </th>
                                <td class="text-end pe-4 fw-semibold">
                                    $ {{ number_format($manoObraTotal ?? 0, 2, ',', '.') }}
                                </td>
                            </tr>

                            <tr>
                                <th class="text-uppercase text-muted ps-4">
                                    Total Material
                                </th>
                                <td class="text-end pe-4 fw-semibold">
                                    $ {{ number_format($solicitudTotal ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            <tr>
                                <th class="text-uppercase text-muted ps-4">
                                    Total Pedido
                                </th>
                                <td class="text-end pe-4 fw-semibold">
                                    $ {{ number_format($pedidoTotal ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            <tr class="table-light">
                                <th class="text-uppercase ps-4">
                                    Utilidad
                                </th>
                                <td
                                    class="text-end pe-4 fw-bold
                                        {{ $utilidad >= 0 ? 'text-success' : 'text-danger' }}">
                                    $ {{ number_format($utilidad ?? 0, 2, ',', '.') }}
                                </td>
                            </tr>

                            <tr>
                                <th class="text-uppercase text-muted ps-4">
                                    Margen %
                                </th>
                                <td
                                    class="text-end pe-4 fw-semibold
                                        {{ $porcentajeUtilidad >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($porcentajeUtilidad ?? 0, 2) }} %
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>
            </div>

        </div>
    </div>


    {{-- BOTÓN --}}
    <div class="text-end">
        <a href="{{ route('ordenes.trabajo.asignadas') }}" class="btn btn-secondary">
            Volver
        </a>
    </div>



@endsection
