@extends('layouts.janji')
@section('title', 'Cek pekerjaan')
@section('content')
@php use App\Support\Rupiah; @endphp
<p>{{ $termin->agreement->user->name }} mencatat bahwa pekerjaan berikut sudah selesai. Silakan cek fotonya.</p>
<article class="card shadow-sm">
    <div class="card-body">
        <h1 class="h5">{{ $termin->title }}</h1>
        <p class="text-muted mb-3">{{ $termin->agreement->title }} · {{ Rupiah::format($termin->amount) }}</p>
        @foreach (['sebelum' => 'Foto sebelum', 'sesudah' => 'Foto sesudah'] as $kind => $label)
            <h2 class="h6">{{ $label }}</h2>
            <div class="row g-2 mb-3">
                @foreach ($photos->where('kind', $kind) as $p)
                    <div class="col-6"><a href="{{ $p['url'] }}"><img class="img-fluid rounded" src="{{ $p['url'] }}" alt="{{ $label }}" loading="lazy"></a></div>
                @endforeach
            </div>
        @endforeach
    </div>
</article>
<form method="POST" action="{{ $url }}" class="d-grid gap-2 mt-3">
    @csrf
    <button name="decision" value="setuju" class="btn btn-success btn-lg">Pekerjaan sudah sesuai</button>
</form>
<p class="small text-muted mt-3">Saat Anda menekan tombol, kami mencatat waktu, alamat IP, dan jenis perangkat Anda. <a href="{{ route('privasi') }}">Selengkapnya</a>. Tautan ini hanya bisa dipakai sekali.</p>
@endsection
