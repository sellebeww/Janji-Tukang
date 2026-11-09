<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Support\InstallmentRows;
use App\Support\Rupiah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WizardController extends Controller
{
    public function lingkup(?Agreement $agreement = null): View
    {
        if ($agreement) {
            $this->authorize('update', $agreement);
        }

        return view('wizard.lingkup', ['agreement' => $agreement]);
    }

    public function saveLingkup(Request $request, ?Agreement $agreement = null): RedirectResponse
    {
        if ($agreement) {
            $this->authorize('update', $agreement);
        }

        $data = $request->validate([
            'creator_role' => ['required', 'in:pemilik,tukang'],
            'title' => ['required', 'string', 'max:150'],
            'counterparty_name' => ['required', 'string', 'max:100'],
            'counterparty_phone' => ['nullable', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'scope' => ['required', 'string', 'max:3000'],
        ]);

        $agreement = $agreement
            ? tap($agreement)->update($data)
            : $request->user()->hasMany(Agreement::class)->create($data);

        return redirect()->route('wizard.harga', $agreement);
    }

    public function harga(Agreement $agreement): View
    {
        $this->authorize('update', $agreement);

        return view('wizard.harga', compact('agreement'));
    }

    public function saveHarga(Request $request, Agreement $agreement): RedirectResponse
    {
        $this->authorize('update', $agreement);

        $total = Rupiah::parse($request->input('total_amount'));
        if ($total === null || $total < 1 || $total > config('janji.money_max')) {
            throw ValidationException::withMessages(['total_amount' => 'Isi harga dengan angka bulat rupiah, contoh 1.500.000.']);
        }

        $agreement->update(['total_amount' => $total]);

        return redirect()->route('wizard.termin', $agreement);
    }

    public function termin(Agreement $agreement): View
    {
        $this->authorize('update', $agreement);
        abort_unless($agreement->total_amount, 404);

        return view('wizard.termin', ['agreement' => $agreement, 'rows' => InstallmentRows::MAX_ROWS]);
    }

    public function saveTermin(Request $request, Agreement $agreement): RedirectResponse
    {
        $this->authorize('update', $agreement);
        abort_unless($agreement->total_amount, 404);

        $rows = InstallmentRows::fromInput($request->input('termin'), $agreement->total_amount);

        $agreement->update(['installments' => $rows]);

        return redirect()->route('agreements.show', $agreement);
    }
}
