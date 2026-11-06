<?php

namespace Database\Seeders;

use App\Models\Agreement;
use App\Models\User;
use App\Services\AgreementFlow;
use App\Services\ConfirmationLink;
use Illuminate\Database\Seeder;

/** Data contoh untuk mencoba alur. Mencetak tautan konfirmasi pihak lawan. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(['email' => 'demo@janjitukang.test'], ['name' => 'Bu Rina', 'password' => bcrypt('password')]);

        $a = Agreement::create([
            'user_id' => $user->id, 'creator_role' => 'pemilik', 'title' => 'Keramik kamar mandi kos Blok B',
            'counterparty_name' => 'Pak Budi', 'counterparty_phone' => '081234567890',
            'scope' => "Bongkar keramik lama.\nPasang keramik 40x40 seluas 6 m2.",
            'total_amount' => 3500000,
            'installments' => [
                ['title' => 'Uang muka', 'amount' => 1000000, 'due_date' => now()->addDays(3)->toDateString()],
                ['title' => 'Pelunasan', 'amount' => 2500000, 'due_date' => now()->addDays(14)->toDateString()],
            ],
        ]);
        $v = app(AgreementFlow::class)->submit($a, null, null);

        $this->command?->info('AGREEMENT '.$a->ulid);
        $this->command?->info('LINK '.app(ConfirmationLink::class)->issue(ConfirmationLink::KONFIRMASI_VERSI, $v->id));
    }
}
