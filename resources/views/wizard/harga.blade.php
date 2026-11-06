@extends('layouts.janji')
@section('title', 'Langkah 2: Harga')
@section('content')
<p class="text-muted mb-1">Langkah 2 dari 3</p>
<h1 class="h4">Berapa harganya?</h1>
@include('partials.errors')
<form method="POST" action="{{ route('wizard.harga', $agreement) }}">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label" for="total_amount">Total harga (rupiah)</label>
        <input class="form-control form-control-lg" id="total_amount" name="total_amount" inputmode="numeric"
               value="{{ old('total_amount', $agreement->total_amount ? \App\Support\Rupiah::format($agreement->total_amount) : '') }}" placeholder="Rp 1.500.000" required>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('wizard.lingkup', $agreement) }}">Kembali</a>
        <button class="btn btn-primary btn-lg flex-grow-1">Lanjut</button>
    </div>
</form>
@endsection
