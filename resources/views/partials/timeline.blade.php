{{-- Fallback tanpa JS. Bila JS jalan, public/js/timeline.js mengganti isi #timeline dengan komponen React dari endpoint JSON. --}}
<div id="timeline" data-src="{{ route('agreements.timeline', $agreement) }}">
    <ol class="list-unstyled mb-0">
        @foreach ($termins as $termin)
            @php $t = $termin->toTimeline(); @endphp
            <li class="border-top py-2">
                <div class="d-flex justify-content-between">
                    <div>
                        <a class="fw-semibold" href="{{ $t['url'] }}">{{ $t['number'] }}. {{ $t['title'] }}</a><br>
                        <small class="text-muted">Jatuh tempo {{ $t['due_label'] }}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold">{{ $t['amount_label'] }}</div>
                        <span class="badge text-bg-{{ ['menunggu' => 'secondary', 'bukti_lengkap' => 'warning', 'selesai' => 'success'][$t['status']] ?? 'secondary' }}">{{ $t['status_label'] }}</span>
                    </div>
                </div>
                @if ($t['photos'])
                    <div class="d-flex gap-1 mt-2 flex-wrap">
                        @foreach ($t['photos'] as $p)
                            <a href="{{ $p['url'] }}"><img src="{{ $p['url'] }}" alt="Foto {{ $p['kind'] }}" width="64" height="64" loading="lazy" class="rounded" style="object-fit: cover;"></a>
                        @endforeach
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</div>
@push('scripts')
    <script src="{{ asset('vendor/react/react.production.min.js') }}"></script>
    <script src="{{ asset('vendor/react/react-dom.production.min.js') }}"></script>
    <script src="{{ asset('js/timeline.js') }}"></script>
@endpush
