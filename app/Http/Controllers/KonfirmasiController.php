<?php

namespace App\Http\Controllers;

use App\Exceptions\LinkException;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Services\AgreementFlow;
use App\Services\ConfirmationLink;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Halaman pihak lawan, tanpa login. Akses hanya lewat tautan bertanda tangan + baris token. */
class KonfirmasiController extends Controller
{
    public function show(Request $request, string $token, ConfirmationLink $links)
    {
        try {
            $t = $links->resolve($token, ConfirmationLink::KONFIRMASI_VERSI);
            $version = AgreementVersion::findOrFail($t->target_id);
            $agreement = Agreement::with('user')->findOrFail($version->agreement_id);
            if ($agreement->status !== 'menunggu_konfirmasi') {
                throw new LinkException('target');
            }
        } catch (LinkException $e) {
            return $this->invalid($e);
        }

        return $this->noStore(response()->view('konfirmasi.show', [
            's' => $agreement->summary(),
            'party' => $agreement->counterpartyRole(),
            'url' => $request->fullUrl(), // sudah bertanda tangan; dipakai sebagai action form
        ]));
    }

    public function store(Request $request, string $token, ConfirmationLink $links, AgreementFlow $flow)
    {
        $decision = $request->validate(['decision' => ['required', 'in:setuju,tolak']])['decision'];

        try {
            $links->consume($token, ConfirmationLink::KONFIRMASI_VERSI,
                fn ($t) => $flow->counterpartyDecides($t, $decision, $request->ip(), $request->userAgent()));
        } catch (LinkException $e) {
            return $this->invalid($e);
        }

        return $this->noStore(response()->view('konfirmasi.selesai', ['decision' => $decision]));
    }

    private function invalid(LinkException $e): Response
    {
        $status = $e->reason === 'tidak_ditemukan' ? 404 : 410;

        return $this->noStore(response()->view('konfirmasi.tidak-berlaku', ['reason' => $e->reason], $status));
    }

    // Token ada di URL: jangan di-cache dan jangan bocor lewat Referer.
    private function noStore(Response $r): Response
    {
        return $r->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex']);
    }
}
