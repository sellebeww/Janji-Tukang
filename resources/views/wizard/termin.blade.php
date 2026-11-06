@extends('layouts.janji')
@section('title', 'Langkah 3: Termin')
@section('content')
@php use App\Support\Rupiah; $old = $agreement->installments ?? []; @endphp
<p class="text-muted mb-1">Langkah 3 dari 3</p>
<h1 class="h4">Dibayar bertahap bagaimana?</h1>
<p>Total harga <strong>{{ Rupiah::format($agreement->total_amount) }}</strong>. Jumlah semua termin harus sama.</p>
@include('partials.errors')
<form method="POST" action="{{ route('wizard.termin', $agreement) }}">
    @csrf @method('PUT')
    @for ($n = 0; $n < $rows; $n++)
        @php $r = old("termin.$n", $old[$n] ?? []); @endphp
        <fieldset class="card card-body mb-2">
            <legend class="h6">Termin {{ $n + 1 }}</legend>
            <input class="form-control mb-2" name="termin[{{ $n }}][title]" aria-label="Judul termin {{ $n + 1 }}" placeholder="Judul, contoh: Uang muka" value="{{ $r['title'] ?? '' }}">
            <input class="form-control mb-2" name="termin[{{ $n }}][amount]" inputmode="numeric" aria-label="Nominal termin {{ $n + 1 }}" placeholder="Nominal, contoh: 500.000" value="{{ isset($r['amount']) ? (is_int($r['amount']) ? Rupiah::format($r['amount']) : $r['amount']) : '' }}">
            <input class="form-control" type="date" name="termin[{{ $n }}][due_date]" aria-label="Jatuh tempo termin {{ $n + 1 }}" value="{{ $r['due_date'] ?? '' }}">
        </fieldset>
    @endfor
    <div class="d-flex gap-2 mt-3">
        <a class="btn btn-outline-secondary" href="{{ route('wizard.harga', $agreement) }}">Kembali</a>
        <button class="btn btn-primary btn-lg flex-grow-1">Lihat ringkasan</button>
    </div>
</form>
@endsection
