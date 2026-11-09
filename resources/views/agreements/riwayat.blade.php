@extends('layouts.janji')
@section('title', 'Riwayat versi')
@section('content')
@php $label = ['berlaku' => ['Berlaku', 'success'], 'menunggu' => ['Menunggu persetujuan', 'warning'], 'ditolak' => ['Ditolak pihak lawan', 'danger'], 'digantikan' => ['Digantikan', 'secondary']]; @endphp
<h1 class="h4">Riwayat: {{ $agreement->title }}</h1>
<a class="small no-print" href="{{ route('agreements.show', $agreement) }}">&larr; Kembali ke ringkasan</a>
@foreach ($history as $h)
    @php $v = $h['version']; [$text, $color] = $label[$h['status']]; @endphp
    <section class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <h2 class="h6 mb-1">Versi {{ $v->number }} · {{ $v->kind === 'addendum' ? 'Perubahan kesepakatan' : 'Kesepakatan awal' }}</h2>
                <span class="badge text-bg-{{ $color }}">{{ $text }}</span>
            </div>
            <p class="small text-muted mb-2">{{ $v->created_at->translatedFormat('j M Y H:i') }} WIB · Kode versi <code>{{ $v->code() }}</code></p>
            @if ($h['diff'])
                @include('partials.perubahan', ['diff' => $h['diff'], 'reason' => $v->reason, 'versus' => 'versi '.$h['prev']->number])
            @else
                <p class="small mb-1">{{ \App\Support\Rupiah::format($v->total_amount) }} · {{ count($v->installments) }} termin</p>
            @endif
            <ul class="list-unstyled small mb-0">
                @foreach ($v->confirmations as $c)
                    <li>{{ ucfirst($c->party) }}: {{ $c->decision === 'setuju' ? 'setuju' : 'menolak' }}, {{ $c->created_at->translatedFormat('j M Y H:i') }} WIB</li>
                @endforeach
            </ul>
        </div>
    </section>
@endforeach
@endsection
