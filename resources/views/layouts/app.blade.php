<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? config('app.name', 'App') }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">


    <style>
        /* Control del ancho del sidebar con variable */
        :root {
            --sb-w: 0;
        }

        /* móvil: 0 por defecto */
        @media (min-width: 768px) {
            :root {
                --sb-w: 18rem;
            }

            /* desktop: abierto = 18rem */
            body.sidebar-closed {
                --sb-w: 0;
            }

            /* desktop: cerrado = 0 */
            body.sidebar-closed #app-sidebar {
                display: none !important;
            }
        }

        /* Forzar que aside y contenido usen la variable */
        #app-sidebar {
            width: var(--sb-w);
        }

        #main-shell {
            padding-left: var(--sb-w);
            transition: padding-left .2s ease;
        }

        .navbar-toggler{
            margin-left: -30px;
        }
    </style>


    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-white text-zinc-900">

    {{-- SIDEBAR FIJO (pegado a la izquierda) --}}
    <aside id="app-sidebar"
        class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-zinc-200 bg-white md:flex md:flex-col">
        {{-- Logo / encabezado del sidebar --}}
        <div class="h-16 flex items-center px-4 border-b border-zinc-200">
            <a href="{{ route('dashboard') }}" class="font-semibold">

                {{ config('app.name', 'App') }}
            </a>



        </div>

        {{-- Navegación del sidebar --}}
        <nav class="flex-1 p-3 space-y-1 ">


            <div class="px-2 text-xs uppercase text-zinc-500 mb-2">Menú</div>
            <a href="{{ route('dashboard') }}"
                class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-zinc-100' }}">
                Dashboard
            </a>

            <a href="{{ route('users.index') }}"
                class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('users.index') ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-zinc-100' }}">
                Usuarios
            </a>



            {{-- Agrega aquí más enlaces de tu app --}}
            {{-- <a href="{{ route('orders.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-zinc-100">Órdenes</a> --}}
            {{-- <a href="{{ route('reports.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-zinc-100">Reportes</a> --}}
        </nav>

        {{-- BLOQUE DE USUARIO (anclado abajo) --}}
        <livewire:user.badge />
    </aside>

    {{-- CONTENEDOR PRINCIPAL (desplazado por el sidebar fijo) --}}
    <div id="main-shell" class="md:pl-72 min-h-screen flex flex-col">

        {{-- HEADER SUPERIOR --}}
        <header class="border-b border-zinc-200 bg-white">

            <div class="h-16 px-4 flex items-center gap-4">

                {{-- Tabs / navegación superior opcional --}}
                <nav class="hidden md:flex items-center gap-2">

                    <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation" id="toggleSidebarDesktop">
                        <span class="navbar-toggler-icon"><i class="fa-solid fa-bars"></i></span>
                    </button>

                    {{-- Más tabs si quieres --}}
                </nav>


            </div>
        </header>

        {{-- CONTENIDO (section amplio) --}}
        <main class="flex-1">
            <section class="px-4 py-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-5">
                    @isset($slot)
                        {{ $slot }} {{-- Livewire Page Component --}}
                    @else
                        @yield('content') {{-- Vista Blade clásica --}}
                    @endisset
                </div>
            </section>
        </main>

        {{-- FOOTER pequeño --}}
        <footer class="border-t border-zinc-200">
            <div class="h-10 px-4 flex items-center text-xs text-zinc-500">
                © {{ date('Y') }} {{ config('app.name', 'App') }}
            </div>
        </footer>
    </div>


    @livewireScripts


    <!-- antes de </body> -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/33667822a1.js" crossorigin="anonymous"></script>


    <script>
        (function() {
            const body = document.body;
            const btn = document.getElementById('toggleSidebarDesktop');

            // restaurar estado guardado
            try {
                if (localStorage.getItem('sidebar-closed') === '1') {
                    body.classList.add('sidebar-closed');
                    btn?.setAttribute('aria-expanded', 'false');
                }
            } catch (e) {}

            // click del botón: cerrar/abrir
            btn?.addEventListener('click', function(e) {
                e.preventDefault(); // ignoramos el data-target del navbar
                const closed = body.classList.toggle('sidebar-closed');
                this.setAttribute('aria-expanded', closed ? 'false' : 'true');
                try {
                    localStorage.setItem('sidebar-closed', closed ? '1' : '0');
                } catch (e) {}
            });
        })();
    </script>


    @stack('scripts')
    @stack('modals')
</body>

</html>
