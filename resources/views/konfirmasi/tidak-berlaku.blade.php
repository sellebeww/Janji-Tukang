@extends('layouts.janji')
@section('title', 'Tautan tidak berlaku')
@section('content')
@php
    $msg = [
        'kedaluwarsa' => 'Tautan ini sudah kedaluwarsa.',
        'dipakai' => 'Tautan ini sudah dipakai.',
        'target' => 'Kesepakatan ini tidak lagi menunggu konfirmasi.',
    ][$reason] ?? 'Tautan tidak dikenal.';
@endphp
<div class="alert alert-warning"><strong>{{ $msg }}</strong><br>Minta pembuat kesepakatan mengirim tautan baru.</div>
@endsection
