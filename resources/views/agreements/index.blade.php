@extends('layouts.janji')
@section('title', 'Kesepakatan saya')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0">Kesepakatan saya</h1>
    <a class="btn btn-primary" href="{{ route('wizard.baru') }}">Buat baru</a>
</div>
@php $label = ['draft' => 'Draf', 'menunggu_konfirmasi' => 'Menunggu konfirmasi', 'aktif' => 'Aktif', 'perlu_diubah' => 'Perlu diubah']; @endphp
@forelse ($agreements as $a)
    <a class="card card-body mb-2 text-decoration-none text-body" href="{{ route('agreements.show', $a) }}">
        <strong>{{ $a->title }}</strong>
        <span class="text-muted">{{ $a->counterparty_name }} · {{ $label[$a->status] ?? $a->status }}</span>
    </a>
@empty
    <p class="text-muted">Belum ada kesepakatan.</p>
@endforelse
@endsection
