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
            $this->assertComplete($agreement);

            $version = AgreementVersion::addTo($agreement, $agreement->scope, $agreement->total_amount, $agreement->installments);
            $this->record($version, $agreement->creator_role, 'setuju', $ip, $userAgent);
            $agreement->update(['status' => 'menunggu_konfirmasi']);

            return $version;
        });
    }

    /** Keputusan pihak lawan lewat token. Dipanggil di dalam ConfirmationLink::consume. */
    public function counterpartyDecides(ConfirmationToken $token, string $decision, ?string $ip, ?string $userAgent): void
    {
        $version = AgreementVersion::findOrFail($token->target_id);
        $agreement = Agreement::whereKey($version->agreement_id)->lockForUpdate()->firstOrFail();

        if ($agreement->status !== 'menunggu_konfirmasi') {
            throw new LinkException('target');
        }

        $this->record($version, $agreement->counterpartyRole(), $decision, $ip, $userAgent);
        $agreement->update(['status' => $decision === 'setuju' ? 'aktif' : 'perlu_diubah']);
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

    private function assertComplete(Agreement $a): void
    {
        $errors = [];
        if (! $a->scope) {
            $errors['scope'] = 'Lingkup pekerjaan belum diisi.';
        }
        if (! $a->total_amount) {
            $errors['total_amount'] = 'Harga belum diisi.';
        }
        if (empty($a->installments)) {
            $errors['installments'] = 'Termin belum diisi.';
        } elseif ($a->total_amount && array_sum(array_column($a->installments, 'amount')) !== $a->total_amount) {
            $errors['installments'] = 'Jumlah termin harus sama dengan total '.Rupiah::format($a->total_amount).'.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
