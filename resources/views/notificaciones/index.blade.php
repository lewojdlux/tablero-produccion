@extends('layouts.app')

@section('content')

<a href="{{ route('ordenes.trabajo.asignados') }}" class="btn btn-sm btn-dark "><i class="fa-solid fa-arrow-left"
title="Volver a la sordenes de trabajo"></i></a>


<div class="max-w-4xl mx-auto space-y-4">


    <h1 class="text-lg font-semibold">Notificaciones</h1>

    <div class="bg-white border rounded-lg divide-y">

        @forelse($notificaciones as $n)
            <a href="{{ route('notificaciones.leer', $n->id) }}"
               class="block px-4 py-3 hover:bg-zinc-50
                      {{ $n->read_at ? 'opacity-60' : '' }}">

                <strong class="text-sm">{{ $n->data['title'] ?? 'Notificación' }}</strong>
                <p class="text-xs text-zinc-600">
                    {{ $n->data['message'] ?? '' }}
                </p>

                <small class="text-[10px] text-zinc-400">
                    {{ $n->created_at->diffForHumans() }}
                </small>
            </a>
        @empty
            <div class="p-6 text-center text-zinc-500 text-sm">
                No hay notificaciones
            </div>
        @endforelse

    </div>

    <div>
        {{ $notificaciones->links() }}
    </div>

</div>
@endsection
