@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-3">

    <h2 class="text-lg font-semibold">Notificaciones</h2>

    @forelse ($notificaciones as $n)
        <div class="p-3 border rounded {{ $n->read_at ? 'bg-white' : 'bg-zinc-50' }}">
            <strong>{{ $n->data['title'] }}</strong>
            <p class="text-sm">{{ $n->data['message'] }}</p>

            @if(isset($n->data['pedido_id']))
                <a href="/pedidos-materiales/{{ $n->data['pedido_id'] }}"
                   class="text-xs text-indigo-600">
                    Ver detalle
                </a>
            @endif
        </div>
    @empty
        <p>No hay notificaciones</p>
    @endforelse

</div>
@endsection
