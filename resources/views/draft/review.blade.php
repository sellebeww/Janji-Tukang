@extends('layouts.janji')
@section('title', 'Tinjau draf')
@section('content')
@php use App\Support\Rupiah; $old = $draft['installments']; @endphp
<h1 class="h4">Tinjau dan sunting draf</h1>
<div class="alert alert-warning">Ini draf otomatis dan belum tersimpan. Periksa setiap isian, terutama harga dan tanggal.</div>
@include('partials.errors')
<form method="POST" action="{{ route('draft.simpan') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="creator_role">Anda sebagai</label>
        <select class="form-select" id="creator_role" name="creator_role">
            @foreach (['pemilik' => 'Pemilik', 'tukang' => 'Tukang / mandor'] as $v => $l)
                <option value="{{ $v }}" @selected(old('creator_role') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label" for="title">Nama pekerjaan</label>
        <input class="form-control" id="title" name="title" maxlength="150" value="{{ old('title', $draft['title']) }}" required></div>
    <div class="mb-3"><label class="form-label" for="counterparty_name">Nama pihak lawan</label>
        <input class="form-control" id="counterparty_name" name="counterparty_name" maxlength="100" value="{{ old('counterparty_name') }}" required></div>
    <div class="mb-3"><label class="form-label" for="counterparty_phone">Nomor WhatsApp pihak lawan (boleh kosong)</label>
        <input class="form-control" id="counterparty_phone" name="counterparty_phone" inputmode="tel" value="{{ old('counterparty_phone') }}"></div>
    <div class="mb-3"><label class="form-label" for="scope">Isi pekerjaan</label>
        <textarea class="form-control" id="scope" name="scope" rows="6" maxlength="3000" required>{{ old('scope', $draft['scope']) }}</textarea></div>
    <div class="mb-3"><label class="form-label" for="total_amount">Total harga</label>
        <input class="form-control" id="total_amount" name="total_amount" inputmode="numeric" value="{{ old('total_amount', $draft['total_amount'] ? Rupiah::format($draft['total_amount']) : '') }}" required></div>
    @for ($n = 0; $n < $rows; $n++)
        @php $r = old("termin.$n", $old[$n] ?? []); @endphp
        <fieldset class="card card-body mb-2">
            <legend class="h6">Termin {{ $n + 1 }}</legend>
            <input class="form-control mb-2" name="termin[{{ $n }}][title]" aria-label="Judul termin {{ $n + 1 }}" value="{{ $r['title'] ?? '' }}">
            <input class="form-control mb-2" name="termin[{{ $n }}][amount]" inputmode="numeric" aria-label="Nominal termin {{ $n + 1 }}" value="{{ isset($r['amount']) ? (is_int($r['amount']) ? Rupiah::format($r['amount']) : $r['amount']) : '' }}">
            <input class="form-control" type="date" name="termin[{{ $n }}][due_date]" aria-label="Jatuh tempo termin {{ $n + 1 }}" value="{{ $r['due_date'] ?? '' }}">
        </fieldset>
    @endfor
    <button class="btn btn-primary btn-lg w-100 mt-3">Simpan sebagai draf</button>
</form>
@endsection
