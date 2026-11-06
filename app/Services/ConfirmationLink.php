<?php

namespace App\Services;

use App\Exceptions\LinkException;
use App\Models\ConfirmationToken;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/** I4: signed URL + baris DB. Satu tautan = satu tujuan + satu target. */
class ConfirmationLink
{
    public const KONFIRMASI_VERSI = 'konfirmasi_versi';

    /** Terbitkan tautan. URL lengkap hanya ada di nilai kembalian ini. */
    public function issue(string $purpose, int $targetId): string
    {
        $raw = Str::random(48);
        $expires = now()->addDays(config('janji.token_ttl_days'));

        ConfirmationToken::create([
            'token_hash' => hash('sha256', $raw),
            'purpose' => $purpose,
            'target_id' => $targetId,
            'expires_at' => $expires,
        ]);

        return URL::temporarySignedRoute('konfirmasi.show', $expires, ['token' => $raw]);
    }

    /** Cek tautan tanpa menghabiskannya (melihat ringkasan boleh berulang). */
    public function resolve(string $raw, string $purpose, ?int $targetId = null, bool $lock = false): ConfirmationToken
    {
        $query = ConfirmationToken::where('token_hash', hash('sha256', $raw));
        $token = ($lock ? $query->lockForUpdate() : $query)->first()
            ?? throw new LinkException('tidak_ditemukan');

        if ($token->purpose !== $purpose) {
            throw new LinkException('tujuan');
        }
        if ($targetId !== null && $token->target_id !== $targetId) {
            throw new LinkException('target');
        }
        if ($token->used_at) {
            throw new LinkException('dipakai');
        }
        if ($token->expires_at->isPast()) {
            throw new LinkException('kedaluwarsa');
        }

        return $token;
    }

    /**
     * Pakai tautan satu kali. $work berjalan di transaksi yang sama dengan penandaan used_at;
     * bila $work melempar exception, used_at ikut dibatalkan.
     */
    public function consume(string $raw, string $purpose, Closure $work, ?int $targetId = null): mixed
    {
        return DB::transaction(function () use ($raw, $purpose, $work, $targetId) {
            $token = $this->resolve($raw, $purpose, $targetId, lock: true);

            $claimed = ConfirmationToken::whereKey($token->id)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->update(['used_at' => now()]);

            if ($claimed !== 1) {
                throw new LinkException('dipakai');
            }

            return $work($token);
        });
    }
}
