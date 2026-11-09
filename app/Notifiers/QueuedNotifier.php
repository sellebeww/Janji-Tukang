<?php

namespace App\Notifiers;

use App\Jobs\SendNotification;

/** Kerangka driver sms / whatsapp_api: hanya mengantre job. Tidak ada klien layanan nyata. */
class QueuedNotifier implements ConfirmationNotifier
{
    public function __construct(private string $channel) {}

    public function send(?string $phone, string $text): ?string
    {
        SendNotification::dispatch($this->channel, $phone, $text);

        return null;
    }
}
