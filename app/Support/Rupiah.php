<?php

namespace App\Support;

final class Rupiah
{
    /** 1500000 -> "Rp 1.500.000" */
    public static function format(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
