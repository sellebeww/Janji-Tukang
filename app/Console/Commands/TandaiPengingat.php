<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class TandaiPengingat extends Command
{
    protected $signature = 'janji:pengingat';

    protected $description = 'Tandai termin yang perlu diingatkan (muncul di dashboard pembuat). Tidak mengirim apa pun.';

    public function handle(ReminderService $svc): int
    {
        $this->info($svc->mark().' pengingat baru.');

        return self::SUCCESS;
    }
}
