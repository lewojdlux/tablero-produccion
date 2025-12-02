@props(['on', 'class' => '', 'timeout' => 4000])

@if (session('status') === $on)
    <p {{ $attributes->merge(['class' => 'text-sm text-green-600 '.$class]) }}>
        {{ $slot }}
    </p>


    <script>
        (() => {
            const el = document.currentScript.previousElementSibling;
            if (!el) return;
            setTimeout(() => {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 220); // espera la transición y elimina
            }, {{ (int) $timeout }});
        })();
    </script>

@endif
