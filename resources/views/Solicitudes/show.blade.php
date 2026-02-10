@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ALERTAS --}}
    @if(session()->has('success'))
        <div class="text-sm text-green-800 bg-green-100 border border-green-300 rounded px-4 py-2">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if(session()->has('warning'))
        <div class="text-sm text-yellow-800 bg-yellow-100 border border-yellow-300 rounded px-4 py-2">
            ⚠️ {{ session('warning') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="text-sm text-red-800 bg-red-100 border border-red-300 rounded px-4 py-2">
            ❌ {{ session('error') }}
        </div>
    @endif


    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">
            Solicitud de Material #{{ $pedido->id_pedido_material }}
        </h2>

        <span class="text-xs px-2 py-1 rounded bg-zinc-100">
            {{ strtoupper($pedido->status) }}
        </span>
    </div>

    {{-- INFO GENERAL --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">

        <div class="border rounded p-3">
            <strong>Instalador</strong><br>
            {{ optional($pedido->instalador)->nombre_instalador ?? '—' }}
        </div>

        <div class="border rounded p-3">
            <strong>Proveedor</strong><br>
            {{ optional($pedido->proveedor)->name_supplier ?? '—' }}
        </div>

        <div class="border rounded p-3">
            <strong>Fecha solicitud</strong><br>
            {{ $pedido->fecha_solicitud }}
        </div>

    </div>



    {{-- TABLA DE MATERIALES --}}
    <div class="overflow-x-auto">
        <table class="w-full text-xs border border-zinc-200">
            <thead class="bg-zinc-100">
                <tr>
                    <th class="border px-2 py-1">Código</th>
                    <th class="border px-2 py-1">Descripción</th>
                    <th class="border px-2 py-1 text-right">Cant.</th>
                    <th class="border px-2 py-1 text-right">Precio</th>
                    <th class="border px-2 py-1 text-right">IVA</th>
                    <th class="border px-2 py-1 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $totalGeneral = 0; @endphp

                @forelse($pedido->detalles as $d)
                    @php $totalGeneral += $d->total; @endphp
                    <tr>
                        <td class="border px-2 py-1">{{ $d->codigo_material }}</td>
                        <td class="border px-2 py-1">{{ $d->descripcion_material }}</td>
                        <td class="border px-2 py-1 text-right">{{ $d->cantidad }}</td>
                        <td class="border px-2 py-1 text-right">
                            {{ number_format($d->precio_unitario, 0, ',', '.') }}
                        </td>
                        <td class="border px-2 py-1 text-right">
                            {{ $d->iva_porcentaje }}%
                        </td>
                        <td class="border px-2 py-1 text-right">
                            {{ number_format($d->total, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-zinc-500">
                            No hay materiales registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot class="bg-zinc-50">
                <tr>
                    <td colspan="5" class="border px-2 py-1 text-right font-semibold">
                        TOTAL
                    </td>
                    <td class="border px-2 py-1 text-right font-semibold">
                        {{ number_format($totalGeneral, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>


    {{-- REIMPORTAR / ADJUNTAR EXCEL --}}
    @if($pedido->status === 'queued')
        <div class="border rounded p-4 bg-zinc-50">

            <h3 class="text-sm font-semibold mb-2">
                📎 Adjuntar / Reimportar Excel
            </h3>

            <form action="{{ route('solicitudes.importExcel', $pedido->orden_trabajo_id) }}"
                method="POST"
                enctype="multipart/form-data"
                class="flex items-center gap-3">

                @csrf

                <input type="file"
                    name="archivo_excel"
                    accept=".xls,.xlsx"
                    required
                    class="text-xs border rounded px-2 py-1">

                <button class="px-4 py-2 text-xs bg-blue-600 text-dark border-dark border-1 rounded hover:bg-blue-700">
                    Importar Excel
                </button>
            </form>

            <p class="text-[11px] text-zinc-500 mt-2">
                • Si el código ya existe, se actualizará.<br>
                • Si el código es nuevo, se agregará.<br>
                • No se eliminarán materiales existentes.
            </p>

        </div>
    @endif




    {{-- BOTONES --}}
    <div class="flex justify-end gap-2">

         @if($pedido->status === 'queued' && in_array(auth()->user()->perfil_usuario_id, [1,2]))
            <form method="POST" action="{{ route('solicitudes.approve', $pedido->id_pedido_material) }}">
                @csrf
                <button
                    class="px-4 py-2 text-xs bg-green-600 text-dark border rounded hover:bg-green-700"
                    onclick="return confirm('¿Desea aprobar esta solicitud y registrar los materiales?')"
                >
                    Aprobar solicitud
                </button>
            </form>
        @endif




        <a href="{{ route('ordenes.trabajo.asignadas') }}"
           class="px-4 py-2 text-xs border rounded hover:bg-zinc-50">
            Volver
        </a>
    </div>

</div>
@endsection
