<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? config('app.name', 'App') }}</title>

    {{-- Bootstrap --}}
    <!--<link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"> -->

    {{-- Estilos base --}}
    <style>
        :root { --sb-w: 0; }

        @media (min-width: 768px) {
            :root { --sb-w: 18rem; }
            body.sidebar-closed { --sb-w: 0; }
            body.sidebar-closed #app-sidebar { display: none !important; }
        }

        #app-sidebar { width: var(--sb-w); }
        #main-shell {
            padding-left: var(--sb-w);
            transition: padding-left .2s ease;
        }

        /* Sidebar compacto */
        #app-sidebar nav a,
        #app-sidebar nav button {
            padding: .45rem .75rem !important;
            font-size: .875rem;
        }

        #app-sidebar nav .ml-6 a {
            padding: .35rem .75rem !important;
            font-size: .8rem;
        }

        #app-sidebar nav .space-y-1 > * {
            margin-bottom: .15rem !important;
        }

        #app-sidebar nav .uppercase {
            font-size: .65rem;
            margin-bottom: .25rem;
        }

        /* Scroll interno del menú */
        #app-sidebar nav {
            overflow-y: auto;
            scrollbar-width: thin;
        }
        #app-sidebar nav::-webkit-scrollbar { width: 5px; }
        #app-sidebar nav::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-white text-zinc-900">

@php
    $perfil = (int) (auth()->user()->perfil_usuario_id ?? 0);
    $isAdmin = in_array($perfil, [1,2], true);
    $isAsesor = $perfil === 5;
    $isInstalador = $perfil === 7;
    $isAdminInstalador =  $perfil === 6;
@endphp

{{-- SIDEBAR --}}
<aside id="app-sidebar"
       class="fixed inset-y-0 left-0 z-40 hidden md:flex md:flex-col
              w-72 border-r border-zinc-200 bg-white">

    {{-- Logo --}}
    <div class="h-16 flex items-center px-4 border-b">
        <a href="{{ route('dashboard') }}" class="font-semibold">
            {{ config('app.name') }}
        </a>
    </div>

    {{-- NAV --}}
    <nav class="flex-1 p-3 space-y-1">

        <div class="px-2 text-xs uppercase text-zinc-500">Menú</div>

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="block rounded-lg
           {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-zinc-100' }}">
            Dashboard
        </a>


        @if ($isAdmin || $isInstalador || $isAdminInstalador)

            {{-- Órdenes de trabajo --}}
            @php $otActive = request()->routeIs('ordenes.trabajo.*'); @endphp

            <button type="button"
                    onclick="toggleMenu('menu-ot')"
                    class="w-full flex justify-between rounded-lg
                    {{ $otActive ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-zinc-100' }}">
                <span>Órdenes de trabajo</span>
                <i class="fa-solid fa-chevron-down text-[10px]
                {{ $otActive ? 'rotate-180' : '' }}"></i>
            </button>

            <div id="menu-ot"
                class="ml-6 space-y-1 {{ $otActive ? '' : 'hidden' }}">
                <a href="{{ route('ordenes.trabajo.asignar') }}"
                class="block rounded-md
                {{ request()->routeIs('ordenes.trabajo.asignar') ? 'bg-indigo-100 text-indigo-700' : 'hover:bg-zinc-100' }}">
                    Asignar OT
                </a>

                <a href="{{ route('ordenes.trabajo.asignadas') }}"
                class="block rounded-md
                {{ request()->routeIs('ordenes.trabajo.asignadas') ? 'bg-indigo-100 text-indigo-700' : 'hover:bg-zinc-100' }}">
                    Asignadas
                </a>
            </div>

        @endif


        @if ($isAdmin  || $isAdminInstalador)

            {{-- Órdenes de trabajo --}}
            @php $otActive = request()->routeIs('pedidos.materiales.*'); @endphp

            <button type="button"
                    onclick="toggleMenu('menu-solicitudes')"
                    class="w-full flex justify-between rounded-lg
                    {{ $otActive ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-zinc-100' }}">
                <span>Solicitudes</span>
                <i class="fa-solid fa-chevron-down text-[10px]
                {{ $otActive ? 'rotate-180' : '' }}"></i>
            </button>

            <div id="menu-solicitudes"
                class="ml-6 space-y-1 {{ $otActive ? '' : 'hidden' }}">

                <a href="{{ route('solicitudes.index') }}"
                class="block rounded-md
                {{ request()->routeIs('solicitudes.index') ? 'bg-indigo-100 text-indigo-700' : 'hover:bg-zinc-100' }}">
                    Realizadas
                </a>
            </div>

        @endif

        {{-- Usuarios --}}
        @if ($isAdmin)
            <a href="{{ route('users.index') }}"
               class="block rounded-lg
               {{ request()->routeIs('users.index') ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-zinc-100' }}">
                Usuarios
            </a>
        @endif

        {{-- Comercial --}}
        @if ($isAdmin || $isAsesor)
            <div class="mt-4 px-2 text-[10px] uppercase text-zinc-400">
                Comercial
            </div>

            <a href="{{ route('portal-crm.seguimiento.index') }}"
               class="block rounded-lg
               {{ request()->routeIs('portal-crm.seguimiento.*') ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-zinc-100' }}">
                Seguimiento CRM
            </a>
        @endif
    </nav>

    {{-- Usuario --}}
    <livewire:user.badge />
</aside>

{{-- MAIN --}}
<div id="main-shell" class="md:pl-72 min-h-screen flex flex-col">

    {{-- Header --}}
    <header class="border-b bg-white">
        <div class="h-16 px-4 flex items-center">
            <button id="toggleSidebarDesktop" class="navbar-toggler">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </header>

    {{-- Contenido --}}
    <main class="flex-1">
        <section class="px-4 py-6">
            <div class="rounded-xl border bg-white p-5">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="border-t">
        <div class="h-10 px-4 flex items-center text-xs text-zinc-500">
            © {{ date('Y') }} {{ config('app.name') }}
        </div>
    </footer>
</div>

@livewireScripts

{{-- JS --}}
<script src="https://kit.fontawesome.com/33667822a1.js" crossorigin="anonymous"></script>

<script>
    function toggleMenu(id) {
        document.getElementById(id)?.classList.toggle('hidden');
    }

    (function () {
        const body = document.body;
        const btn = document.getElementById('toggleSidebarDesktop');

        if (localStorage.getItem('sidebar-closed') === '1') {
            body.classList.add('sidebar-closed');
        }

        btn?.addEventListener('click', function () {
            const closed = body.classList.toggle('sidebar-closed');
            localStorage.setItem('sidebar-closed', closed ? '1' : '0');
        });
    })();
</script>

@stack('scripts')
</body>
</html>
