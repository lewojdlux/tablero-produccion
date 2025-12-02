<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ config('app.name','App') }} — Login</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  @livewireStyles
</head>
<body class="min-h-screen bg-white text-zinc-900">
  <div class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4">
    {{ $slot }}
  </div>
  @livewireScripts
</body>
</html>
