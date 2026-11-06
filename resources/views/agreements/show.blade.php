@extends('layouts.janji')
@section('title', $s['title'])
@section('content')
@php $label = ['draft' => 'Draf', 'menunggu_konfirmasi' => 'Menunggu konfirmasi pihak lawan', 'aktif' => 'Aktif: kedua pihak sudah setuju', 'perlu_diubah' => 'Pihak lawan meminta perubahan']; @endphp
<p class="badge text-bg-secondary no-print">{{ $label[$agreement->status] ?? $agreement->status }}</p>
@include('partials.errors')
@include('partials.summary')

@if ($agreement->isDraft())
    <div class="no-print d-grid gap-2 mt-3">
        <form method="POST" action="{{ route('agreements.submit', $agreement) }}">@csrf
            <button class="btn btn-primary btn-lg w-100">Ajukan ke pihak lawan</button>
        </form>
        <a class="btn btn-outline-secondary" href="{{ route('wizard.lingkup', $agreement) }}">Ubah isian</a>
    </div>
@endif

@if ($agreement->status === 'menunggu_konfirmasi')
    <div class="no-print mt-3">
        @if (session('tautan'))
            <div class="card card-body border-success mb-2">
                <p class="mb-2">Tautan hanya tampil sekali. Kirim sekarang:</p>
                @if (session('wa'))
                    <a class="btn btn-success btn-lg mb-2" href="{{ session('wa') }}" target="_blank" rel="noopener">Kirim lewat WhatsApp</a>
                @endif
                <input class="form-control" readonly value="{{ session('tautan') }}" onfocus="this.select()" aria-label="Tautan konfirmasi">
            </div>
        @endif
        <form method="POST" action="{{ route('agreements.link', $agreement) }}">@csrf
            <button class="btn btn-outline-primary w-100">Buat tautan konfirmasi baru</button>
        </form>
    </div>
@endif

@if ($confirmations->isNotEmpty())
    <h2 class="h6 mt-4">Riwayat konfirmasi</h2>
    <ul class="list-unstyled small">
        @foreach ($confirmations as $c)
            <li>{{ ucfirst($c->party) }}: {{ $c->decision === 'setuju' ? 'setuju' : 'minta perubahan' }}, {{ $c->created_at->translatedFormat('j M Y H:i') }} WIB</li>
        @endforeach
    </ul>
@endif
<button class="btn btn-outline-secondary mt-3 no-print" onclick="window.print()">Cetak</button>
@endsection
