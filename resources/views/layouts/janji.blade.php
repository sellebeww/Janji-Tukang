<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Janji Tukang') · Janji Tukang</title>
    {{-- Sementara dari CDN karena npm belum bisa dipakai. Ganti ke @vite saat Bootstrap terpasang (lihat docs/DECISIONS.md). --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; }
        .form-control, .form-select { min-height: 44px; }
        @media print { .no-print { display: none !important; } body { background: #fff; } }
    </style>
</head>
<body class="bg-light">
<nav class="navbar bg-white border-bottom no-print">
    <div class="container" style="max-width: 720px;">
        <a class="navbar-brand fw-bold" href="{{ auth()->check() ? route('agreements.index') : url('/') }}">Janji Tukang</a>
        @auth
            <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf
                <button class="btn btn-outline-secondary btn-sm">Keluar</button>
            </form>
        @endauth
    </div>
</nav>
<main class="container py-3" style="max-width: 720px;">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @yield('content')
</main>
<footer class="container pb-4 text-muted small" style="max-width: 720px;">
    Ini catatan bersama dua pihak, bukan nasihat hukum. Aplikasi tidak memegang uang.
    <a href="{{ route('privasi') }}">Privasi</a>
</footer>
@stack('scripts')
</body>
</html>
