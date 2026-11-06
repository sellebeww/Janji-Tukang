<?php

use App\Models\Agreement;
use App\Models\User;
use App\Services\AgreementFlow;
use App\Services\ConfirmationLink;

/** Kesepakatan sudah diajukan (menunggu pihak lawan). Mengembalikan [agreement, version]. */
function f2Submitted(?User $user = null): array
{
    $a = Agreement::create([
        'user_id' => ($user ?? User::factory()->create())->id,
        'creator_role' => 'pemilik',
        'title' => 'Keramik kamar mandi',
        'counterparty_name' => 'Pak Budi',
        'counterparty_phone' => '08123456789',
        'scope' => 'Pasang keramik 2x3 m',
        'total_amount' => 1500000,
        'installments' => [
            ['title' => 'Uang muka', 'amount' => 500000, 'due_date' => '2026-11-20'],
            ['title' => 'Pelunasan', 'amount' => 1000000, 'due_date' => '2026-12-05'],
        ],
    ]);
    $v = app(AgreementFlow::class)->submit($a, '127.0.0.1', 'tes');

    return [$a->fresh(), $v];
}

function f2Url(int $versionId): string
{
    return app(ConfirmationLink::class)->issue(ConfirmationLink::KONFIRMASI_VERSI, $versionId);
}
