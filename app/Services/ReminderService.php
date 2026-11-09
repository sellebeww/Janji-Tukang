<?php

namespace App\Services;

use App\Models\ReminderLog;
use App\Models\Termin;

class ReminderService
{
    /** Tandai termin belum selesai yang jatuh tempo <= N hari lagi (atau sudah lewat). Idempoten. Mengembalikan jumlah baru. */
    public function mark(): int
    {
        $today = today();
        $new = 0;

        Termin::where('status', '!=', 'selesai')
            ->whereHas('agreement', fn ($q) => $q->where('status', 'aktif'))
            ->whereDate('due_date', '<=', $today->copy()->addDays(config('janji.reminder_days_before')))
            ->each(function (Termin $t) use ($today, &$new) {
                $new += ReminderLog::insertOrIgnore([
                    'ulid' => (string) \Illuminate\Support\Str::ulid(),
                    'termin_id' => $t->id,
                    'kind' => $t->due_date->lt($today) ? 'lewat' : 'jelang',
                    'created_at' => now(),
                ]);
            });

        return $new;
    }
}
