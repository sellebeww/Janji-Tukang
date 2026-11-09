<?php

namespace App\Services;

use App\Exceptions\LinkException;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\ConfirmationToken;
use App\Models\VersionConfirmation;
use App\Support\Rupiah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** draft -> menunggu_konfirmasi -> aktif (atau perlu_diubah bila pihak lawan menolak). */
class AgreementFlow
{
    public function submit(Agreement $agreement, ?string $ip, ?string $userAgent): AgreementVersion
    {
        return DB::transaction(function () use ($agreement, $ip, $userAgent) {
            $agreement = Agreement::whereKey($agreement->id)->lockForUpdate()->firstOrFail();

            if ($agreement->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Kesepakatan ini sudah diajukan.']);
            }
            $this->assertComplete($agreement->scope, $agreement->total_amount, $agreement->installments);

            $version = AgreementVersion::addTo($agreement, $agreement->scope, $agreement->total_amount, $agreement->installments);
            $this->record($version, $agreement->creator_role, 'setuju', $ip, $userAgent);
            $agreement->update(['status' => 'menunggu_konfirmasi']);

            return $version;
        });
    }

    /**
     * "Ubah kesepakatan": versi baru (addendum) dengan alasan wajib. Versi lama tidak disentuh (I2);
     * versi berlaku tetap yang lama sampai kedua pihak setuju (I3). Usulan baru menggantikan usulan yang masih menunggu.
     */
    public function propose(Agreement $agreement, string $scope, int $total, array $installments, string $reason, ?string $ip, ?string $userAgent): AgreementVersion
    {
        return DB::transaction(function () use ($agreement, $scope, $total, $installments, $reason, $ip, $userAgent) {
            $agreement = Agreement::whereKey($agreement->id)->lockForUpdate()->firstOrFail();

            if (! in_array($agreement->status, ['aktif', 'perlu_diubah'], true)) {
                throw ValidationException::withMessages(['status' => 'Kesepakatan ini belum bisa diubah lewat addendum.']);
            }
            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => 'Alasan perubahan wajib diisi.']);
            }
            $this->assertComplete($scope, $total, $installments);

            $effective = $agreement->effectiveVersion();
            $base = $effective ?? $agreement->versions()->reorder('number', 'desc')->first();
            if ($base && $base->scope === $scope && $base->total_amount === $total && $base->installments == $installments) {
                throw ValidationException::withMessages(['scope' => 'Tidak ada yang berubah dari versi sebelumnya.']);
            }
            if ($effective && $conflicts = app(TerminFlow::class)->conflicts($agreement, $installments)) {
                throw ValidationException::withMessages(['termin' => $conflicts]);
            }

            $version = AgreementVersion::addTo($agreement, $scope, $total, $installments, $reason);
            $this->record($version, $agreement->creator_role, 'setuju', $ip, $userAgent);
            if (! $effective) {
                $agreement->update(['status' => 'menunggu_konfirmasi']);
            }

            return $version;
        });
    }

    /** Keputusan pihak lawan lewat token. Dipanggil di dalam ConfirmationLink::consume. */
    public function counterpartyDecides(ConfirmationToken $token, string $decision, ?string $ip, ?string $userAgent): void
    {
        $version = AgreementVersion::findOrFail($token->target_id);
        $agreement = Agreement::whereKey($version->agreement_id)->lockForUpdate()->firstOrFail();

        // Hanya usulan yang masih ditunggu yang bisa diputuskan; tautan versi lama/terganti mati.
        if ($agreement->pendingVersion()?->id !== $version->id) {
            throw new LinkException('target');
        }

        $this->record($version, $agreement->counterpartyRole(), $decision, $ip, $userAgent);

        if ($decision === 'setuju') {
            $agreement->update(['status' => 'aktif']);
            $terminFlow = app(TerminFlow::class);
            $version->number === 1 ? $terminFlow->materialize($agreement) : $terminFlow->apply($agreement, $version);
        } elseif (! $agreement->effectiveVersion()) {
            $agreement->update(['status' => 'perlu_diubah']); // ada versi berlaku: kesepakatan tetap aktif
        }
    }

    private function record(AgreementVersion $version, string $party, string $decision, ?string $ip, ?string $userAgent): void
    {
        VersionConfirmation::create([
            'agreement_version_id' => $version->id,
            'party' => $party,
            'decision' => $decision,
            'ip' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 255, '') : null,
        ]);
    }

    private function assertComplete(?string $scope, ?int $total, ?array $installments): void
    {
        $errors = [];
        if (! $scope) {
            $errors['scope'] = 'Lingkup pekerjaan belum diisi.';
        }
        if (! $total) {
            $errors['total_amount'] = 'Harga belum diisi.';
        }
        if (empty($installments)) {
            $errors['installments'] = 'Termin belum diisi.';
        } elseif ($total && array_sum(array_column($installments, 'amount')) !== $total) {
            $errors['installments'] = 'Jumlah termin harus sama dengan total '.Rupiah::format($total).'.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
