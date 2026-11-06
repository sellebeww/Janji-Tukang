<?php

namespace App\Notifiers;

/** Tanpa biaya API: pembuat menekan tombol kirim di WhatsApp. */
class WaLinkNotifier implements ConfirmationNotifier
{
    public function send(?string $phone, string $text): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }
}
