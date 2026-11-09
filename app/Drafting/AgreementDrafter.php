<?php

namespace App\Drafting;

interface AgreementDrafter
{
    /**
     * Ubah cerita bebas menjadi DRAF: [title, scope, total_amount, installments[]].
     * Hasil tidak pernah disimpan; pengguna meninjau dan menyunting dulu.
     *
     * @throws DraftException
     */
    public function draft(string $story): array;
}
