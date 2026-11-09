<?php

namespace App\Http\Controllers;

use App\Models\ReminderLog;
use App\Notifiers\ConfirmationNotifier;
use Illuminate\Http\RedirectResponse;

class PengingatController extends Controller
{
    /** Tombol kirim: catat sent_at lalu buka wa_link (atau driver lain). Menekan dua kali tidak menggandakan catatan. */
    public function send(ReminderLog $log, ConfirmationNotifier $notifier): RedirectResponse
    {
        $this->authorize('view', $log->termin->agreement);

        if ($log->termin->status === 'selesai') {
            return redirect()->route('agreements.index')->with('status', 'Termin itu sudah selesai.');
        }

        $log->sent_at ??= now();
        $log->save();

        $url = $notifier->send($log->termin->agreement->counterparty_phone, $log->text());

        return $url ? redirect()->away($url) : redirect()->route('agreements.index')->with('status', 'Pengingat dikirim.');
    }
}
