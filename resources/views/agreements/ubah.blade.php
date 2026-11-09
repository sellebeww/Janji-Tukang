@extends('layouts.janji')
@section('title', 'Ubah kesepakatan')
@section('content')
@php use App\Support\Rupiah; $old = $base->installments; @endphp
<h1 class="h4">Ubah kesepakatan</h1>
<p>Perubahan dibuat sebagai versi baru. Versi {{ $base->number }} tidak diubah dan tetap berlaku sampai pihak lawan setuju.</p>
@include('partials.errors')
<form method="POST" action="{{ route('addendum.store', $agreement) }}">
    @csrf
    <label class="form-label" for="reason">Alasan perubahan (wajib)</label>
    <textarea class="form-control mb-3" id="reason" name="reason" rows="2" maxlength="1000" required>{{ old('reason') }}</textarea>
    <label class="form-label" for="scope">Lingkup pekerjaan</label>
    <textarea class="form-control mb-3" id="scope" name="scope" rows="4" maxlength="3000" required>{{ old('scope', $base->scope) }}</textarea>
    <label class="form-label" for="total_amount">Harga total</label>
    <input class="form-control mb-3" id="total_amount" name="total_amount" inputmode="numeric" value="{{ old('total_amount', Rupiah::format($base->total_amount)) }}">
    @for ($n = 0; $n < $rows; $n++)
        @php $r = old("termin.$n", $old[$n] ?? []); @endphp
        <fieldset class="card card-body mb-2">
            <legend class="h6">Termin {{ $n + 1 }}</legend>
            <input class="form-control mb-2" name="termin[{{ $n }}][title]" aria-label="Judul termin {{ $n + 1 }}" placeholder="Judul" value="{{ $r['title'] ?? '' }}">
            <input class="form-control mb-2" name="termin[{{ $n }}][amount]" inputmode="numeric" aria-label="Nominal termin {{ $n + 1 }}" placeholder="Nominal" value="{{ isset($r['amount']) ? (is_int($r['amount']) ? Rupiah::format($r['amount']) : $r['amount']) : '' }}">
            <input class="form-control" type="date" name="termin[{{ $n }}][due_date]" aria-label="Jatuh tempo termin {{ $n + 1 }}" value="{{ $r['due_date'] ?? '' }}">
        </fieldset>
    @endfor
    <div class="d-flex gap-2 mt-3">
        <a class="btn btn-outline-secondary" href="{{ route('agreements.show', $agreement) }}">Batal</a>
        <button class="btn btn-primary btn-lg flex-grow-1">Buat usulan perubahan</button>
    </div>
</form>
@endsection
