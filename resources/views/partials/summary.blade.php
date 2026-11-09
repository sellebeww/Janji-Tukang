{{-- $s: hasil Agreement::summary() --}}
@php use App\Support\Rupiah; use Illuminate\Support\Carbon; @endphp
<article class="card shadow-sm">
    <div class="card-body">
        <h1 class="h4">{{ $s['title'] }}</h1>
        <p class="text-muted mb-3">
            Catatan kesepakatan @if ($s['version_no']) · Versi {{ $s['version_no'] }} · Kode versi <code>{{ $s['version_code'] }}</code> @else · Draf @endif
        </p>
        <dl class="row mb-0">
            <dt class="col-sm-3">{{ ucfirst($s['creator_role']) }}</dt><dd class="col-sm-9">{{ $s['creator_name'] }}</dd>
            <dt class="col-sm-3">{{ $s['creator_role'] === 'pemilik' ? 'Tukang' : 'Pemilik' }}</dt><dd class="col-sm-9">{{ $s['counterparty_name'] }}</dd>
            <dt class="col-sm-3">Lingkup</dt><dd class="col-sm-9" style="white-space: pre-line;">{{ $s['scope'] }}</dd>
            <dt class="col-sm-3">Harga</dt><dd class="col-sm-9 fw-bold">{{ $s['total'] ? Rupiah::format($s['total']) : '-' }}</dd>
        </dl>
        <h2 class="h6 mt-3">Termin</h2>
        @forelse ($s['installments'] as $i)
            <div class="d-flex justify-content-between border-top py-2">
                <div>{{ $loop->iteration }}. {{ $i['title'] }}<br>
                    <small class="text-muted">Jatuh tempo {{ Carbon::parse($i['due_date'])->translatedFormat('j M Y') }}</small></div>
                <div class="text-end fw-semibold">{{ Rupiah::format($i['amount']) }}</div>
            </div>
        @empty
            <p class="text-muted">Belum ada termin.</p>
        @endforelse
    </div>
</article>
