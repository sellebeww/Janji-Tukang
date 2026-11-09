@extends('layouts.janji')
@section('title', 'Kesepakatan saya')
@section('content')
<div class="jt-hello">
    <div class="small text-uppercase fw-bold" style="color:#d6d3cc;letter-spacing:.08em">Halo, {{ auth()->user()->name }}</div>
    <div class="d-flex gap-4 mt-2">
        <div><div class="stat">{{ $agreements->where('status', 'aktif')->count() }}</div><small>aktif</small></div>
        <div><div class="stat">{{ $agreements->whereIn('status', ['menunggu_konfirmasi', 'perlu_diubah'])->count() }}</div><small>menunggu</small></div>
        <div><div class="stat">{{ $agreements->where('status', 'selesai')->count() }}</div><small>selesai</small></div>
    </div>
</div>
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
        <span class="d-flex justify-content-between align-items-center gap-2"><span class="text-muted">{{ $a->counterparty_name }}</span>
            <span class="jt-chip {{ $a->status }}">{{ $label[$a->status] ?? $a->status }}</span></span>
    </a>
@empty
    <div class="card card-body text-center py-4"><div class="mx-auto rounded-4 p-2" style="background:#111113"><img class="jt-mark" src="{{ asset('img/logo-mark.png') }}" alt="" width="96"></div><p class="text-muted my-2">Belum ada kesepakatan.</p><a class="btn btn-primary mx-auto" href="{{ route('wizard.baru') }}">Buat yang pertama</a></div>
@endforelse
@endsection
