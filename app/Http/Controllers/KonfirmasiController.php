<?php

namespace App\Http\Controllers;

use App\Exceptions\LinkException;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\Termin;
use App\Models\TerminPhoto;
use App\Services\AgreementFlow;
use App\Services\ConfirmationLink;
use App\Services\TerminFlow;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/** Halaman pihak lawan, tanpa login. Akses hanya lewat tautan bertanda tangan + baris token. */
class KonfirmasiController extends Controller
{
    public function show(Request $request, string $token, ConfirmationLink $links)
    {
        if ($links->purposeOf($token) === ConfirmationLink::KONFIRMASI_TERMIN) {
            return $this->showTermin($request, $token, $links);
        }

        try {
            $t = $links->resolve($token, ConfirmationLink::KONFIRMASI_VERSI);
            $version = AgreementVersion::findOrFail($t->target_id);
            $agreement = Agreement::with('user')->findOrFail($version->agreement_id);
            if ($agreement->pendingVersion()?->id !== $version->id) {
                throw new LinkException('target');
            }
        } catch (LinkException $e) {
            return $this->invalid($e);
        }

        return $this->noStore(response()->view('konfirmasi.show', [
            's' => $agreement->summary($version),
            'version' => $version,
            'diff' => $version->kind === 'addendum' ? $version->diffFrom($agreement->effectiveVersion() ?? $agreement->versions()->where('number', '<', $version->number)->reorder('number', 'desc')->first()) : null,
            'base' => $agreement->effectiveVersion(),
            'party' => $agreement->counterpartyRole(),
            'url' => $request->fullUrl(), // sudah bertanda tangan; dipakai sebagai action form
        ]));
    }

    public function store(Request $request, string $token, ConfirmationLink $links, AgreementFlow $flow, TerminFlow $terminFlow)
    {
        if ($links->purposeOf($token) === ConfirmationLink::KONFIRMASI_TERMIN) {
            return $this->storeTermin($request, $token, $links, $terminFlow);
        }

        $decision = $request->validate(['decision' => ['required', 'in:setuju,tolak']])['decision'];

        try {
            $addendum = $links->consume($token, ConfirmationLink::KONFIRMASI_VERSI, function ($t) use ($flow, $decision, $request) {
                $flow->counterpartyDecides($t, $decision, $request->ip(), $request->userAgent());

                return AgreementVersion::find($t->target_id)->kind === 'addendum';
            });
        } catch (LinkException $e) {
            return $this->invalid($e);
        }

        return $this->noStore(response()->view('konfirmasi.selesai', ['decision' => $decision, 'addendum' => $addendum]));
    }

    private function showTermin(Request $request, string $token, ConfirmationLink $links)
    {
        try {
            $t = $links->resolve($token, ConfirmationLink::KONFIRMASI_TERMIN);
            $termin = Termin::with(['agreement.user', 'photos'])->findOrFail($t->target_id);
            if ($termin->status !== 'bukti_lengkap' || $termin->agreement->status !== 'aktif') {
                throw new LinkException('target');
            }
        } catch (LinkException $e) {
            return $this->invalid($e);
        }

        // Foto lewat URL bertanda tangan milik token ini; tidak ada akses tanpa token yang masih berlaku.
        $photos = $termin->photos->map(fn (TerminPhoto $p) => [
            'kind' => $p->kind,
            'url' => URL::temporarySignedRoute('konfirmasi.foto', $t->expires_at, ['token' => $token, 'photo' => $p->ulid]),
        ]);

        return $this->noStore(response()->view('konfirmasi.termin', [
            'termin' => $termin, 'photos' => $photos, 'url' => $request->fullUrl(),
        ]));
    }

    private function storeTermin(Request $request, string $token, ConfirmationLink $links, TerminFlow $flow)
    {
        try {
            $termin = $links->consume($token, ConfirmationLink::KONFIRMASI_TERMIN, function ($t) use ($request, $flow) {
                $termin = Termin::with('agreement')->findOrFail($t->target_id);
                $flow->confirm($termin, $termin->agreement->counterpartyRole(), $request->ip(), $request->userAgent());

                return $termin;
            });
        } catch (LinkException $e) {
            return $this->invalid($e);
        } catch (ValidationException) { // termin tidak lagi bisa ditutup; used_at ikut dibatalkan
            return $this->invalid(new LinkException('target'));
        }

        return $this->noStore(response()->view('konfirmasi.selesai', ['decision' => 'setuju', 'termin' => $termin]));
    }

    public function foto(string $token, string $photo, ConfirmationLink $links)
    {
        try {
            $t = $links->resolve($token, ConfirmationLink::KONFIRMASI_TERMIN);
        } catch (LinkException) {
            abort(404);
        }
        $p = TerminPhoto::where('ulid', $photo)->where('termin_id', $t->target_id)->firstOrFail();

        return Storage::disk('local')->response($p->path, null, [
            'Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer',
        ]);
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
