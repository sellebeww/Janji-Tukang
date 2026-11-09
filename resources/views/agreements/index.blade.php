@extends('layouts.janji')
@section('title', 'Kesepakatan saya')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0">Kesepakatan saya</h1>
    <span><a class="btn btn-outline-secondary" href="{{ route('draft.form') }}">Dari cerita</a>
    <a class="btn btn-primary" href="{{ route('wizard.baru') }}">Buat baru</a></span>
</div>
@if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
@if ($reminders->isNotEmpty())
<div class="card card-body mb-3 border-warning">
    <h2 class="h6">Perlu diingatkan</h2>
    @foreach ($reminders as $r)
        <form method="POST" action="{{ route('pengingat.kirim', $r) }}" class="d-flex justify-content-between align-items-center mb-2">
            @csrf
            <span>{{ $r->termin->agreement->title }} · termin {{ $r->termin->position }} ·
                <strong>{{ $r->kind === 'lewat' ? 'lewat' : 'jatuh tempo' }} {{ $r->termin->due_date->translatedFormat('j M Y') }}</strong>
                ({{ $r->termin->agreement->counterparty_name }})</span>
            <button class="btn btn-sm btn-success">Kirim pengingat</button>
        </form>
    @endforeach
</div>
@endif
@php $label = ['draft' => 'Draf', 'menunggu_konfirmasi' => 'Menunggu konfirmasi', 'aktif' => 'Aktif', 'perlu_diubah' => 'Perlu diubah', 'selesai' => 'Selesai']; @endphp
@forelse ($agreements as $a)
    <a class="card card-body mb-2 text-decoration-none text-body" href="{{ route('agreements.show', $a) }}">
        <strong>{{ $a->title }}</strong>
        <span class="text-muted">{{ $a->counterparty_name }} · {{ $label[$a->status] ?? $a->status }}</span>
    </a>
@empty
    <p class="text-muted">Belum ada kesepakatan.</p>
@endforelse
@endsection
