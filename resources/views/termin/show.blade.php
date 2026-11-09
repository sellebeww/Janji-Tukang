@extends('layouts.janji')
@section('title', $termin->title)
@section('content')
@php
    $a = $termin->agreement;
    $locked = $termin->status === 'selesai' || $termin->confirmations->isNotEmpty();
    $cp = $a->counterpartyRole();
    $done = collect($t['confirmed']);
@endphp
<p><a href="{{ route('agreements.show', $a) }}">&larr; {{ $a->title }}</a></p>
@include('partials.errors')

<article class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <div>
                <h1 class="h5 mb-1">{{ $t['number'] }}. {{ $t['title'] }}</h1>
                <small class="text-muted">Jatuh tempo {{ $t['due_label'] }}</small>
            </div>
            <div class="text-end">
                <div class="fw-bold">{{ $t['amount_label'] }}</div>
                <span class="badge text-bg-{{ ['menunggu' => 'secondary', 'bukti_lengkap' => 'warning', 'selesai' => 'success'][$t['status']] ?? 'secondary' }}">{{ $t['status_label'] }}</span>
            </div>
        </div>
        <p class="small mb-0 mt-2">
            Konfirmasi: {{ ucfirst($a->creator_role) }} {{ $done->contains($a->creator_role) ? 'sudah' : 'belum' }},
            {{ ucfirst($cp) }} {{ $done->contains($cp) ? 'sudah' : 'belum' }}.
        </p>
    </div>
</article>

@foreach (['sebelum' => 'Foto sebelum', 'sesudah' => 'Foto sesudah'] as $kind => $label)
    <section class="card card-body mb-3">
        <h2 class="h6">{{ $label }}</h2>
        <div class="row g-2 mb-2">
            @forelse ($termin->photos->where('kind', $kind) as $p)
                <div class="col-6">
                    <a href="{{ route('foto.tampil', $p) }}"><img class="img-fluid rounded" src="{{ route('foto.tampil', $p) }}" alt="{{ $label }}" loading="lazy"></a>
                    <small class="text-muted d-block">{{ $p->created_at->translatedFormat('j M Y H:i') }} WIB</small>
                    @unless ($locked)
                        <form method="POST" action="{{ route('foto.hapus', $p) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm">Hapus</button>
                        </form>
                    @endunless
                </div>
            @empty
                <p class="text-muted mb-0">Belum ada.</p>
            @endforelse
        </div>
        @unless ($locked)
            {{-- Tanpa atribut capture: di ponsel, pemilih file menawarkan kamera dan galeri. Daftar tipe eksplisit supaya iOS mengirim JPEG, bukan HEIC. --}}
            <form method="POST" action="{{ route('termin.foto.simpan', $termin) }}" enctype="multipart/form-data" class="d-grid gap-2">@csrf
                <input type="hidden" name="jenis" value="{{ $kind }}">
                <input class="form-control" type="file" name="foto" accept="image/jpeg,image/png,image/webp" required aria-label="{{ $label }}">
                <button class="btn btn-primary">Unggah {{ strtolower($label) }}</button>
            </form>
        @endunless
    </section>
@endforeach

@if ($termin->status === 'menunggu')
    <p class="text-muted">Termin baru bisa ditutup setelah ada foto sebelum dan foto sesudah.</p>
@elseif ($termin->status === 'bukti_lengkap')
    <div class="d-grid gap-2">
        @unless ($done->contains($a->creator_role))
            <form method="POST" action="{{ route('termin.konfirmasi', $termin) }}" onsubmit="return confirm('Konfirmasi bahwa pekerjaan ini selesai sesuai foto? Foto akan terkunci.')">@csrf
                <button class="btn btn-success btn-lg w-100">Saya konfirmasi pekerjaan ini selesai</button>
            </form>
        @endunless
        @unless ($done->contains($cp))
            @if (session('tautan'))
                <div class="card card-body border-success">
                    <p class="mb-2">Tautan hanya tampil sekali. Kirim ke {{ $a->counterparty_name }}:</p>
                    @if (session('wa'))
                        <a class="btn btn-success mb-2" href="{{ session('wa') }}" target="_blank" rel="noopener">Kirim lewat WhatsApp</a>
                    @endif
                    <input class="form-control" readonly value="{{ session('tautan') }}" onfocus="this.select()" aria-label="Tautan konfirmasi">
                </div>
            @endif
            <form method="POST" action="{{ route('termin.tautan', $termin) }}">@csrf
                <button class="btn btn-outline-primary w-100">Buat tautan konfirmasi untuk {{ $a->counterparty_name }}</button>
            </form>
        @endunless
    </div>
@endif
@endsection
