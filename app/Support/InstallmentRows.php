<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Baca baris termin dari formulir (wizard dan addendum): bersihkan, validasi, cocokkan dengan total. */
class InstallmentRows
{
    public const MAX_ROWS = 6;

    public static function fromInput(mixed $input, int $total): array
    {
        // Baris kosong diabaikan; angka dibersihkan lewat Rupiah::parse.
        $rows = collect($input ?? [])
            ->filter(fn ($r) => filled($r['title'] ?? null) || filled($r['amount'] ?? null))
            ->take(self::MAX_ROWS)
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
        if ($sum !== $total) {
            throw ValidationException::withMessages(['termin' => sprintf(
                'Jumlah termin %s, harus sama dengan total %s (selisih %s).',
                Rupiah::format($sum), Rupiah::format($total), Rupiah::format(abs($sum - $total)),
            )]);
        }

        return $rows->all();
    }
}
