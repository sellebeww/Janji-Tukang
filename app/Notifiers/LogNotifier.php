<?php

namespace App\Notifiers;

use Illuminate\Support\Facades\Log;

/** Untuk dev/test. */
class LogNotifier implements ConfirmationNotifier
{
    public function send(?string $phone, string $text): ?string
    {
        Log::info('ConfirmationNotifier', ['phone' => $phone, 'text' => $text]);

        return null;
    }
}
