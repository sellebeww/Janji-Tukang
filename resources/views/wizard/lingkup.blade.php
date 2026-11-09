@extends('layouts.janji')
@section('title', 'Langkah 1: Lingkup')
@section('content')
<div class="jt-steps-label">Langkah 1 dari 3</div><div class="jt-steps" role="progressbar" aria-valuemin="1" aria-valuemax="3" aria-valuenow="1"><i class="on"></i><i></i><i></i></div>
<h1 class="h4">Pekerjaan apa yang disepakati?</h1>
@include('partials.errors')
<form method="POST" action="{{ $agreement ? route('wizard.lingkup', $agreement) : route('wizard.simpan') }}">
    @csrf @if ($agreement) @method('PUT') @endif
    <div class="mb-3">
        <label class="form-label" for="creator_role">Anda sebagai</label>
        <select class="form-select" id="creator_role" name="creator_role">
            @foreach (['pemilik' => 'Pemilik', 'tukang' => 'Tukang / mandor'] as $v => $l)
                <option value="{{ $v }}" @selected(old('creator_role', $agreement?->creator_role) === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label" for="title">Nama pekerjaan</label>
        <input class="form-control" id="title" name="title" maxlength="150" value="{{ old('title', $agreement?->title) }}" placeholder="Contoh: Keramik kamar mandi" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="counterparty_name">Nama pihak lawan</label>
        <input class="form-control" id="counterparty_name" name="counterparty_name" maxlength="100" value="{{ old('counterparty_name', $agreement?->counterparty_name) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="counterparty_phone">Nomor WhatsApp pihak lawan (boleh kosong)</label>
        <input class="form-control" id="counterparty_phone" name="counterparty_phone" inputmode="tel" value="{{ old('counterparty_phone', $agreement?->counterparty_phone) }}" placeholder="08123456789">
    </div>
    <div class="mb-3">
        <label class="form-label" for="scope">Isi pekerjaan</label>
        <textarea class="form-control" id="scope" name="scope" rows="6" maxlength="3000" required>{{ old('scope', $agreement?->scope) }}</textarea>
    </div>
    <button class="btn btn-primary btn-lg w-100">Lanjut</button>
</form>
@endsection
