<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Support\Rupiah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WizardController extends Controller
{
    private const MAX_TERMIN_ROWS = 6;

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

        return view('wizard.termin', ['agreement' => $agreement, 'rows' => self::MAX_TERMIN_ROWS]);
    }

    public function saveTermin(Request $request, Agreement $agreement): RedirectResponse
    {
        $this->authorize('update', $agreement);
        abort_unless($agreement->total_amount, 404);

        // Baris kosong diabaikan; angka dibersihkan lewat Rupiah::parse.
        $rows = collect($request->input('termin', []))
            ->filter(fn ($r) => filled($r['title'] ?? null) || filled($r['amount'] ?? null))
            ->take(self::MAX_TERMIN_ROWS)
            ->map(fn ($r) => [
                'title' => trim((string) ($r['title'] ?? '')),
                'amount' => Rupiah::parse($r['amount'] ?? null),
                'due_date' => $r['due_date'] ?? null,
            ])->values();

        Validator::make(['termin' => $rows->all()], [
            'termin' => ['required', 'array', 'min:1'],
            'termin.*.title' => ['required', 'string', 'max:150'],
            'termin.*.amount' => ['required', 'integer', 'min:1'],
            'termin.*.due_date' => ['required', 'date_format:Y-m-d'],
        ], [
            'termin.required' => 'Isi minimal satu termin.',
            'termin.min' => 'Isi minimal satu termin.',
            'termin.*.title.required' => 'Judul termin wajib diisi.',
            'termin.*.amount.required' => 'Nominal termin harus angka bulat rupiah.',
            'termin.*.amount.integer' => 'Nominal termin harus angka bulat rupiah.',
            'termin.*.amount.min' => 'Nominal termin harus lebih dari nol.',
            'termin.*.due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
            'termin.*.due_date.date_format' => 'Tanggal jatuh tempo tidak valid.',
        ])->validate();

        $sum = $rows->sum('amount');
        if ($sum !== $agreement->total_amount) {
            throw ValidationException::withMessages(['termin' => sprintf(
                'Jumlah termin %s, harus sama dengan total %s (selisih %s).',
                Rupiah::format($sum), Rupiah::format($agreement->total_amount),
                Rupiah::format(abs($sum - $agreement->total_amount)),
            )]);
        }

        $agreement->update(['installments' => $rows->all()]);

        return redirect()->route('agreements.show', $agreement);
    }
}
