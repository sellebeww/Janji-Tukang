{{-- $diff: hasil AgreementVersion::diffFrom(); $reason: alasan; $versus: teks pembanding --}}
@php use App\Support\Rupiah; use Illuminate\Support\Carbon; $fmt = fn ($i) => $i ? $i['title'].', '.Rupiah::format($i['amount']).', jatuh tempo '.Carbon::parse($i['due_date'])->translatedFormat('j M Y') : '-'; @endphp
<div class="card border-warning mb-3">
    <div class="card-body">
        <h2 class="h6">Perubahan kesepakatan</h2>
        <p class="small text-muted mb-2">Dibanding {{ $versus }}</p>
        @isset($reason)<p class="mb-2"><strong>Alasan:</strong> <span style="white-space: pre-line;">{{ $reason }}</span></p>@endisset
        @if (! $diff['scope'] && ! $diff['total'] && ! $diff['installments'])
            <p class="mb-0 text-muted">Tidak ada perubahan isi.</p>
        @endif
        @if ($diff['scope'])
            <p class="mb-1"><strong>Lingkup</strong></p>
            <p class="mb-2 small"><del style="white-space: pre-line;">{{ $diff['scope'][0] }}</del><br><ins style="white-space: pre-line;">{{ $diff['scope'][1] }}</ins></p>
        @endif
        @if ($diff['total'])
            <p class="mb-2"><strong>Harga</strong>: <del>{{ Rupiah::format($diff['total'][0]) }}</del> &rarr; <ins>{{ Rupiah::format($diff['total'][1]) }}</ins></p>
        @endif
        @foreach ($diff['installments'] as $r)
            <p class="mb-2"><strong>Termin {{ $r['no'] }}</strong> ({{ ['ubah' => 'diubah', 'tambah' => 'ditambah', 'hapus' => 'dihapus'][$r['type']] }})<br>
                @if ($r['old'])<del class="small">{{ $fmt($r['old']) }}</del><br>@endif
                @if ($r['new'])<ins class="small">{{ $fmt($r['new']) }}</ins>@endif</p>
        @endforeach
    </div>
</div>
