<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $channel, public ?string $phone, public string $text) {}

    public function handle(): void
    {
        if (! config("janji.channels.{$this->channel}.enabled")) {
            Log::warning('Driver notifikasi nonaktif, pesan tidak dikirim', ['channel' => $this->channel]);

            return;
        }

        // Sengaja gagal: klien sms/WhatsApp API belum ditulis. Isi di sini saat ada kontrak dan kredensial.
        throw new \LogicException("Klien {$this->channel} belum diimplementasikan.");
    }
}
