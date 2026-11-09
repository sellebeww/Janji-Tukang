@extends('layouts.janji')
@section('title', 'Cek kesepakatan')
@section('content')
@if ($diff)
<p>Pembuat kesepakatan mengusulkan perubahan. Sebelum Anda setuju, kesepakatan yang lama tetap berlaku.</p>
@include('partials.perubahan', ['diff' => $diff, 'reason' => $version->reason, 'versus' => $base ? 'versi '.$base->number.' yang berlaku' : 'versi sebelumnya'])
@else
<p>Silakan cek catatan kesepakatan ini.</p>
@endif
@include('partials.summary')
<form method="POST" action="{{ $url }}" class="d-grid gap-2 mt-3">
    @csrf
    <button name="decision" value="setuju" class="btn btn-success btn-lg">Saya setuju</button>
    <button name="decision" value="tolak" class="btn btn-outline-secondary btn-lg">Ada yang perlu diubah</button>
</form>
<p class="small text-muted mt-3">Saat Anda menekan tombol, kami mencatat waktu, alamat IP, dan jenis perangkat Anda. <a href="{{ route('privasi') }}">Selengkapnya</a>. Tautan ini hanya bisa dipakai sekali.</p>
@endsection
