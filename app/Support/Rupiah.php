<?php

namespace App\Support;

final class Rupiah
{
    /** 1500000 -> "Rp 1.500.000" */
    public static function format(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * "1.500.000", "Rp 1500000" -> 1500000. Desimal atau format ganjil -> null (tidak menebak).
     */
    public static function parse(?string $input): ?int
    {
        $s = preg_replace('/^Rp\s*|\s+/i', '', trim((string) $input));

        if (! preg_match('/^(\d{1,3}(\.\d{3})+|\d+)$/', $s)) {
            return null;
        }

        return (int) str_replace('.', '', $s);
    }
}
