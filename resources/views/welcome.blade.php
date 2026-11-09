<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111113">
    <title>Janji Tukang · Catatan kesepakatan renovasi yang jelas</title>
    <meta name="description" content="Catat lingkup, harga, dan termin pekerjaan bersama tukang atau pemilik rumah. Kedua pihak konfirmasi lewat tautan, tanpa pasang aplikasi.">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/janji.css') }}">
</head>
<body>
<header class="jt-hero">
    <img class="jt-mark logo" src="{{ asset('img/logo-full.png') }}" alt="Janji Tukang" width="520" height="347">
    <p class="lead">Catat lingkup kerja, harga, dan termin bersama tukang atau pemilik rumah. Kedua pihak konfirmasi lewat satu tautan, tanpa pasang aplikasi.</p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        @auth
            <a class="btn btn-primary btn-lg px-4" href="{{ route('agreements.index') }}">Buka kesepakatan saya</a>
        @else
            <a class="btn btn-primary btn-lg px-4" href="{{ route('register') }}">Mulai gratis</a>
            <a class="btn btn-outline-light btn-lg px-4" href="{{ route('login') }}">Masuk</a>
        @endauth
    </div>
</header>

<main class="container pb-5" style="max-width: 860px;">
    <div class="row g-3 jt-how">
        @foreach ([
            ['Tulis janjinya', 'Isi pekerjaan, harga, dan termin pembayaran. Bisa dibantu dari cerita singkat.'],
            ['Kirim ke pihak lawan', 'Satu tautan lewat WhatsApp. Mereka cek dan setuju sekali tekan.'],
            ['Pantau sampai selesai', 'Foto bukti per termin, pengingat jatuh tempo, dan riwayat versi yang tidak bisa diubah diam-diam.'],
        ] as [$judul, $isi])
            <div class="col-md-4">
                <div class="card">
                    <div class="no">{{ $loop->iteration }}</div>
                    <h2 class="h6">{{ $judul }}</h2>
                    <p class="text-muted small mb-0">{{ $isi }}</p>
                </div>
            </div>
        @endforeach
    </div>
    <p class="text-muted small text-center mt-4 mb-0">
        Ini catatan bersama dua pihak, bukan nasihat hukum. Aplikasi tidak memegang uang.
        <a href="{{ route('privasi') }}">Privasi</a>
    </p>
</main>
<script>
document.querySelectorAll('.jt-how .card').forEach((el, i) => { el.setAttribute('data-reveal', ''); el.style.setProperty('--i', i);
    requestAnimationFrame(() => setTimeout(() => el.classList.add('in'), 60)); });
</script>
</body>
</html>
