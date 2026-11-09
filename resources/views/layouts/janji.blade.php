<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#111113">
    <title>@yield('title', 'Janji Tukang') · Janji Tukang</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,700,800&display=swap" rel="stylesheet">
    {{-- Sementara dari CDN karena npm belum bisa dipakai. Ganti ke @vite saat Bootstrap terpasang (lihat docs/DECISIONS.md). --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/janji.css') }}">
    <style>
        .btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; }
        .form-control, .form-select { min-height: 44px; }
        @media print { .no-print { display: none !important; } body { background: #fff; } [data-reveal] { opacity: 1 !important; transform: none !important; } }
    </style>
</head>
<body @auth class="has-dock" @endauth>
<div class="jt-progress no-print" id="jt-progress"></div>
<nav class="jt-top no-print">
    <div class="inner">
        <a class="jt-brand" href="{{ auth()->check() ? route('agreements.index') : url('/') }}">
            <img class="jt-mark" src="{{ asset('img/logo-mark.png') }}" alt="" width="52" height="42">
            <span>Janji <b>Tukang</b></span>
        </a>
        @auth
            <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf
                <button class="btn btn-ghost btn-sm">Keluar</button>
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
@auth
    <nav class="jt-dock no-print" aria-label="Menu utama">
        <a href="{{ route('agreements.index') }}" @class(['on' => request()->routeIs('agreements.index')])>Kesepakatan</a>
        <a href="{{ route('wizard.baru') }}" @class(['on' => request()->routeIs('wizard.*')])>+ Buat baru</a>
        <a href="{{ route('profile.edit') }}" @class(['on' => request()->routeIs('profile.*')])>Profil</a>
    </nav>
@endauth
<script>
(() => {
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Muncul bertahap: kartu dan elemen bertanda data-reveal.
    const items = document.querySelectorAll('main .card, main .jt-hello, main [data-reveal]');
    items.forEach((el, i) => { el.setAttribute('data-reveal', ''); el.style.setProperty('--i', Math.min(i, 8)); });
    const show = el => el.classList.add('in');
    if (reduce || !('IntersectionObserver' in window)) items.forEach(show);
    else {
        const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { show(e.target); io.unobserve(e.target); } }), { threshold: .08 });
        items.forEach(el => io.observe(el));
    }
    // Bilah kemajuan gulir.
    const bar = document.getElementById('jt-progress');
    addEventListener('scroll', () => {
        const h = document.documentElement;
        bar.style.width = (h.scrollTop / Math.max(1, h.scrollHeight - h.clientHeight) * 100) + '%';
    }, { passive: true });
    // Cegah kirim ganda: tombol berputar setelah form dikirim. Ditunda agar nilai tombol (name/value) tetap terkirim.
    document.addEventListener('submit', e => {
        if (e.defaultPrevented) return;
        setTimeout(() => e.target.querySelectorAll('button:not([type=button])').forEach(b => b.classList.add('is-busy')), 0);
    });
    addEventListener('pageshow', () => document.querySelectorAll('.is-busy').forEach(b => b.classList.remove('is-busy')));
})();
</script>
@stack('scripts')
</body>
</html>
