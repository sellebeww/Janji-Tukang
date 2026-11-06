<?php

namespace App\Notifiers;

interface ConfirmationNotifier
{
    /**
     * Siapkan/kirim teks ke pihak lawan.
     * Mengembalikan URL yang harus ditekan pembuat (wa_link), atau null bila tidak ada langkah manual.
     */
    public function send(?string $phone, string $text): ?string;
}
