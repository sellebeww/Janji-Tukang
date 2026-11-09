@extends('layouts.janji')
@section('title', 'Terima kasih')
@section('content')
@isset($termin)
    <div class="alert alert-success"><strong>Terima kasih.</strong> Konfirmasi Anda untuk "{{ $termin->title }}" sudah dicatat.</div>
@elseif ($addendum ?? false)
    @if ($decision === 'setuju')
        <div class="alert alert-success"><strong>Terima kasih.</strong> Persetujuan Anda sudah dicatat. Perubahan kesepakatan sekarang berlaku.</div>
    @else
        <div class="alert alert-info"><strong>Terima kasih.</strong> Penolakan Anda sudah dicatat. Kesepakatan yang lama tetap berlaku.</div>
    @endif
@elseif ($decision === 'setuju')
    <div class="alert alert-success"><strong>Terima kasih.</strong> Persetujuan Anda sudah dicatat. Kesepakatan ini sekarang aktif.</div>
@else
    <div class="alert alert-info"><strong>Terima kasih.</strong> Permintaan perubahan Anda sudah dicatat. Pembuat kesepakatan akan menghubungi Anda.</div>
@endif
@endsection
