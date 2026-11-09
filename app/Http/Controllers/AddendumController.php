<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Services\AgreementFlow;
use App\Support\InstallmentRows;
use App\Support\Rupiah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** "Ubah kesepakatan" (addendum) dan halaman riwayat versi. */
class AddendumController extends Controller
{
    public function create(Agreement $agreement): View
    {
        $this->authorize('amend', $agreement);
        $base = $agreement->effectiveVersion() ?? $agreement->versions()->reorder('number', 'desc')->firstOrFail();

        return view('agreements.ubah', ['agreement' => $agreement, 'base' => $base, 'rows' => InstallmentRows::MAX_ROWS]);
    }

    public function store(Request $request, Agreement $agreement, AgreementFlow $flow): RedirectResponse
    {
        $this->authorize('amend', $agreement);

        $data = $request->validate([
            'scope' => ['required', 'string', 'max:3000'],
            'reason' => ['required', 'string', 'max:1000'],
        ], ['reason.required' => 'Alasan perubahan wajib diisi.', 'scope.required' => 'Lingkup pekerjaan wajib diisi.']);

        $total = Rupiah::parse($request->input('total_amount'));
        if ($total === null || $total < 1 || $total > config('janji.money_max')) {
            throw ValidationException::withMessages(['total_amount' => 'Isi harga dengan angka bulat rupiah, contoh 1.500.000.']);
        }
        $rows = InstallmentRows::fromInput($request->input('termin'), $total);

        $flow->propose($agreement, $data['scope'], $total, $rows, $data['reason'], $request->ip(), $request->userAgent());

        return redirect()->route('agreements.show', $agreement)
            ->with('status', 'Usulan perubahan dibuat. Kirim tautan ke pihak lawan; sebelum ia setuju, versi lama tetap berlaku.');
    }

    public function history(Agreement $agreement): View
    {
        $this->authorize('view', $agreement);
        $history = $agreement->history();

        return view('agreements.riwayat', [
            'agreement' => $agreement,
            'history' => collect($history)->map(function ($h, $i) use ($history) {
                $prev = $history[$i - 1]['version'] ?? null; // pembanding = versi sebelumnya dalam rantai
                $h['diff'] = $h['version']->kind === 'addendum' ? $h['version']->diffFrom($prev) : null;
                $h['prev'] = $prev;

                return $h;
            })->reverse()->values(),
        ]);
    }
}
