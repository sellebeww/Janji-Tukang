<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Termin;
use App\Models\TerminPhoto;
use App\Notifiers\ConfirmationNotifier;
use App\Services\ConfirmationLink;
use App\Services\TerminFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Semua akses lewat AgreementPolicy::view (bukan pemilik = 404). Termin hidup selalu salinan versi berlaku. */
class TerminController extends Controller
{
    /** Data timeline untuk komponen React. Termin dibuat dari versi berlaku bila belum ada. */
    public function timeline(Agreement $agreement, TerminFlow $flow): JsonResponse
    {
        $this->authorize('view', $agreement);
        $flow->materialize($agreement);

        return response()->json(['termins' => $agreement->termins()->with(['photos', 'confirmations'])->get()->map->toTimeline()])
            ->header('Cache-Control', 'no-store, private');
    }

    public function show(Termin $termin): View
    {
        $this->authorize('view', $termin->agreement);

        return view('termin.show', [
            'termin' => $termin->load(['agreement', 'photos', 'confirmations']),
            't' => $termin->toTimeline(),
        ]);
    }

    public function storePhoto(Request $request, Termin $termin, TerminFlow $flow): RedirectResponse
    {
        $this->authorize('view', $termin->agreement);

        $data = $request->validate([
            'jenis' => ['required', 'in:sebelum,sesudah'],
            // mimetypes memeriksa isi file (finfo), bukan nama/ekstensi/Content-Type dari klien.
            'foto' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
        ], [
            'foto.required' => 'Pilih foto dulu.',
            'foto.uploaded' => 'Foto gagal terunggah. Ukurannya mungkin terlalu besar atau koneksi putus. Coba lagi.',
            'foto.mimetypes' => 'File bukan foto. Gunakan JPEG, PNG, atau WebP.',
            'foto.max' => 'Foto lebih dari 10 MB.',
        ]);
        $flow->addPhoto($termin, $data['jenis'], $request->file('foto'), $request->user());

        return redirect()->route('termin.show', $termin)->with('status', 'Foto tersimpan.');
    }

    public function destroyPhoto(TerminPhoto $photo, TerminFlow $flow): RedirectResponse
    {
        $this->authorize('view', $photo->termin->agreement);
        $flow->deletePhoto($photo);

        return redirect()->route('termin.show', $photo->termin)->with('status', 'Foto dihapus.');
    }

    public function photo(TerminPhoto $photo)
    {
        $this->authorize('view', $photo->termin->agreement);

        return Storage::disk('local')->response($photo->path, null, [
            'Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    public function confirm(Request $request, Termin $termin, TerminFlow $flow): RedirectResponse
    {
        $this->authorize('view', $termin->agreement);
        $flow->confirm($termin, $termin->agreement->creator_role, $request->ip(), $request->userAgent());

        return redirect()->route('termin.show', $termin)->with('status', 'Konfirmasi Anda tercatat.');
    }

    /** Tautan konfirmasi termin untuk pihak lawan. Hanya bila bukti lengkap. */
    public function link(Termin $termin, ConfirmationLink $links, ConfirmationNotifier $notifier): RedirectResponse
    {
        $this->authorize('view', $termin->agreement);
        $a = $termin->agreement;
        if ($termin->status !== 'bukti_lengkap' || $termin->confirmations()->where('party', $a->counterpartyRole())->exists()) {
            throw ValidationException::withMessages(['termin' => 'Tautan hanya bisa dibuat saat bukti lengkap dan pihak lawan belum mengonfirmasi.']);
        }

        $url = $links->issue(ConfirmationLink::KONFIRMASI_TERMIN, $termin->id);
        $text = sprintf(
            'Halo %s, pekerjaan "%s" (%s) sudah ada foto sebelum dan sesudah. Mohon dicek lalu konfirmasi lewat tautan ini (berlaku %d hari): %s',
            $a->counterparty_name, $termin->title, $a->title, config('janji.token_ttl_days'), $url,
        );

        return redirect()->route('termin.show', $termin)->with([
            'tautan' => $url,
            'wa' => $notifier->send($a->counterparty_phone, $text),
        ]);
    }
}
