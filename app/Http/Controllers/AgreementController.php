<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Notifiers\ConfirmationNotifier;
use App\Services\AgreementFlow;
use App\Services\ConfirmationLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgreementController extends Controller
{
    public function index(Request $request): View
    {
        return view('agreements.index', [
            'agreements' => $request->user()->hasMany(Agreement::class)->latest()->get(),
        ]);
    }

    /** Ringkasan satu halaman (ramah cetak). */
    public function show(Agreement $agreement): View
    {
        $this->authorize('view', $agreement);

        $confirmations = $agreement->versions()->with('confirmations')->get()->flatMap->confirmations;

        return view('agreements.show', [
            'agreement' => $agreement,
            's' => $agreement->summary(),
            'confirmations' => $confirmations,
        ]);
    }

    public function submit(Request $request, Agreement $agreement, AgreementFlow $flow): RedirectResponse
    {
        $this->authorize('update', $agreement);
        $flow->submit($agreement, $request->ip(), $request->userAgent());

        return redirect()->route('agreements.show', $agreement)
            ->with('status', 'Kesepakatan diajukan. Kirim tautan ke pihak lawan.');
    }

    /** Buat tautan baru + teks siap kirim. Tautan hanya ditampilkan sekali. */
    public function link(Agreement $agreement, ConfirmationLink $links, ConfirmationNotifier $notifier): RedirectResponse
    {
        $this->authorize('link', $agreement);

        $version = $agreement->versions()->reorder('number', 'desc')->firstOrFail();
        $url = $links->issue(ConfirmationLink::KONFIRMASI_VERSI, $version->id);

        $text = sprintf(
            "Halo %s, ini catatan kesepakatan kerja \"%s\" dari %s. Mohon dicek lalu konfirmasi lewat tautan ini (berlaku %d hari): %s",
            $agreement->counterparty_name, $agreement->title, $agreement->user->name,
            config('janji.token_ttl_days'), $url,
        );

        return redirect()->route('agreements.show', $agreement)->with([
            'tautan' => $url,
            'wa' => $notifier->send($agreement->counterparty_phone, $text),
        ]);
    }
}
