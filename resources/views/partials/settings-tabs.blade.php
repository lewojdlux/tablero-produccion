@php
  $items = [
    ['key'=>'profile',  'label'=>__('Profile'),  'route'=>route('settings.profile'),  'active'=> request()->routeIs('settings.profile')],
    ['key'=>'password', 'label'=>__('Password'), 'route'=>route('settings.password'), 'active'=> request()->routeIs('settings.password')],
  ];
@endphp

<nav class="mb-0">
  <ul class="flex gap-1">
    @foreach($items as $it)
      <li>
        @if($it['active'])
          {{-- Pestaña activa: borde y fondo blanco, sin borde inferior --}}
          <a href="{{ $it['route'] }}"
             class="relative -mb-px inline-flex items-center rounded-t-xl border border-zinc-200 border-b-0 bg-white px-3 py-2 text-sm font-medium text-indigo-700">
            {{ $it['label'] }}
          </a>
        @else
          {{-- Pestaña inactiva --}}
          <a href="{{ $it['route'] }}"
             class="inline-flex items-center rounded-t-xl px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900">
            {{ $it['label'] }}
          </a>
        @endif
      </li>
    @endforeach
  </ul>
</nav>
