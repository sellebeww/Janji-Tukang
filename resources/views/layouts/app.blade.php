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
    <body class="antialiased has-dock" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--jt-bg); margin: 0;">
        <nav class="jt-top">
            <div class="inner">
                <a class="jt-brand" href="{{ route('agreements.index') }}">
                    <img class="jt-mark" src="{{ asset('img/logo-mark.png') }}" alt="" width="52" height="42">
                    <span>Janji <b>Tukang</b></span>
                </a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button class="btn-ghost" style="cursor:pointer">Keluar</button>
                </form>
            </div>
        </nav>

        @isset($header)
            <header style="max-width: 720px; margin: 0 auto; padding: 1.25rem 1rem 0;">
                {{ $header }}
            </header>
        @endisset

        <main style="max-width: 720px; margin: 0 auto; padding-bottom: 5.5rem;">
            {{ $slot }}
        </main>

        <nav class="jt-dock" aria-label="Menu utama">
            <a href="{{ route('agreements.index') }}">Kesepakatan</a>
            <a href="{{ route('wizard.baru') }}">+ Buat baru</a>
            <a href="{{ route('profile.edit') }}" class="on">Profil</a>
        </nav>
    </body>
</html>
