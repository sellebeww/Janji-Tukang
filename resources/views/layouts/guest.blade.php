<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#111113">

        <title>{{ config('app.name', 'Janji Tukang') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/janji.css') }}">
    </head>
    <body style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif; margin: 0;" class="antialiased">
        <div class="jt-auth">
            <div style="width: 100%; max-width: 420px;">
                <a href="/" aria-label="Janji Tukang">
                    <img class="jt-mark logo" src="{{ asset('img/logo-full.png') }}" alt="Janji Tukang" width="240" height="160">
                </a>
                <div class="panel">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
