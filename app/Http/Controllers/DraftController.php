<?php

namespace App\Http\Controllers;

use App\Drafting\AgreementDrafter;
use App\Drafting\DraftException;
use App\Models\Agreement;
use App\Support\InstallmentRows;
use App\Support\Rupiah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cerita bebas -> draf yang DITINJAU di formulir. Tidak ada yang tersimpan sebelum pengguna menekan simpan. */
class DraftController extends Controller
{
    public function form(): View
    {
        return view('draft.form');
    }

    public function generate(Request $request, AgreementDrafter $drafter): View|RedirectResponse
    {
        $story = $request->validate(['story' => ['required', 'string', 'max:3000']])['story'];

        try {
            $draft = $drafter->draft($story);
        } catch (DraftException $e) {
            return back()->withInput()->withErrors(['story' => $e->getMessage().' Coba lagi atau isi lewat "Buat baru".']);
        }

        return view('draft.review', ['draft' => $draft, 'rows' => InstallmentRows::MAX_ROWS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'creator_role' => ['required', 'in:pemilik,tukang'],
            'title' => ['required', 'string', 'max:150'],
            'counterparty_name' => ['required', 'string', 'max:100'],
            'counterparty_phone' => ['nullable', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'scope' => ['required', 'string', 'max:3000'],
        ]);

        $total = Rupiah::parse($request->input('total_amount'));
        if ($total === null || $total < 1 || $total > config('janji.money_max')) {
            return back()->withInput()->withErrors(['total_amount' => 'Isi harga dengan angka bulat rupiah, contoh 1.500.000.']);
        }

        $data += ['total_amount' => $total, 'installments' => InstallmentRows::fromInput($request->input('termin'), $total)];
        $agreement = $request->user()->hasMany(Agreement::class)->create($data);

        return redirect()->route('agreements.show', $agreement);
    }
}
