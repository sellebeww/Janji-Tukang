<?php

namespace App\Drafting;

/** Tanpa AI: cerita masuk apa adanya ke kolom "isi pekerjaan"; pengguna mengisi sisanya di formulir. */
class ManualDrafter implements AgreementDrafter
{
    public function draft(string $story): array
    {
        return ['title' => '', 'scope' => $story, 'total_amount' => null, 'installments' => []];
    }
}
