@if($user = auth()->user())
  <details class="mt-auto border-t border-zinc-200 p-3 group">
    <summary class="flex cursor-pointer list-none items-center gap-2 px-1 py-1.5 rounded-lg hover:bg-zinc-100">
      <span class="grid place-items-center h-8 w-8 rounded-lg bg-neutral-200 text-black text-sm font-semibold">
        {{ mb_strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}
      </span>
      <div class="grid flex-1 text-start leading-tight">
        <span class="truncate font-semibold text-sm">{{ $user->name }}</span>
        <span class="truncate text-xs text-zinc-500">{{ $user->email }}</span>
      </div>
      <svg class="h-4 w-4 shrink-0 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
      </svg>
    </summary>

    <nav class="mt-2 space-y-1">
      <a href="{{ route('settings.profile') }}"
         class="block w-full rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 {{ request()->routeIs('settings.profile') ? 'bg-indigo-50 text-indigo-700' : '' }}">
        Perfil
      </a>
      <a href="{{ route('settings.password') }}"
         class="block w-full rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 {{ request()->routeIs('settings.password') ? 'bg-indigo-50 text-indigo-700' : '' }}">
        Contraseña
      </a>

      {{-- Si luego agregas Apariencia:
      <a href="{{ route('settings.appearance') }}" class="block w-full rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 {{ request()->routeIs('settings.appearance') ? 'bg-indigo-50 text-indigo-700' : '' }}">
        Apariencia
      </a>
      --}}

      <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <button type="submit" class="w-full text-left rounded-lg px-3 py-2 text-sm hover:bg-zinc-100">
          Cerrar sesión
        </button>
      </form>
    </nav>
  </details>
@else
  <div class="border-t border-zinc-200 p-3 text-sm">
    <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-700 underline">Iniciar sesión</a>
  </div>
@endif
