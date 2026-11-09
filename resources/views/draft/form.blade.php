@extends('layouts.janji')
@section('title', 'Draf dari cerita')
@section('content')
<h1 class="h4">Ceritakan pekerjaannya</h1>
<p class="text-muted">Hasilnya hanya draf. Anda tinjau dan sunting dulu; tidak ada yang tersimpan sebelum Anda menekan simpan.</p>
@include('partials.errors')
<form method="POST" action="{{ route('draft.generate') }}">
    @csrf
    <textarea class="form-control mb-3" name="story" rows="8" maxlength="3000" aria-label="Cerita pekerjaan" required>{{ old('story') }}</textarea>
    <button class="btn btn-primary btn-lg w-100">Buat draf</button>
</form>
@endsection
