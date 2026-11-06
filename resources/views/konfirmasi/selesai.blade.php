@extends('layouts.janji')
@section('title', 'Terima kasih')
@section('content')
@if ($decision === 'setuju')
    <div class="alert alert-success"><strong>Terima kasih.</strong> Persetujuan Anda sudah dicatat. Kesepakatan ini sekarang aktif.</div>
@else
    <div class="alert alert-info"><strong>Terima kasih.</strong> Permintaan perubahan Anda sudah dicatat. Pembuat kesepakatan akan menghubungi Anda.</div>
@endif
@endsection
