<?php

use App\Jobs\SendNotification;
use App\Models\ReminderLog;
use App\Models\User;
use App\Notifiers\ConfirmationNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

require_once __DIR__.'/../Fase2/Helpers.php';

afterEach(fn () => Carbon::setTestNow());

function f5Aktif(): array
{
    $owner = User::factory()->create();
    [$a, $v] = f2Submitted($owner);
    test()->post(f2Url($v->id), ['decision' => 'setuju'])->assertOk();

    return [$owner, $a->fresh()];
}

it('menandai termin jelang tempo dan tidak menggandakan saat dijalankan dua kali', function () {
    f5Aktif();
    Carbon::setTestNow('2026-11-19 07:00'); // termin 1 jatuh tempo 20 Nov, termin 2 masih jauh

    $this->artisan('janji:pengingat')->expectsOutput('1 pengingat baru.')->assertSuccessful();
    $this->artisan('janji:pengingat')->expectsOutput('0 pengingat baru.')->assertSuccessful();

    expect(ReminderLog::count())->toBe(1)->and(ReminderLog::first()->kind)->toBe('jelang');

    Carbon::setTestNow('2026-11-25'); // sekarang lewat: jenis baru, sekali saja
    $this->artisan('janji:pengingat');
    $this->artisan('janji:pengingat');
    expect(ReminderLog::pluck('kind')->all())->toBe(['jelang', 'lewat']);
});

it('tidak menandai termin selesai atau kesepakatan yang belum aktif', function () {
    [, $a] = f5Aktif();
    f2Submitted(); // menunggu konfirmasi
    $a->termins()->first()->update(['status' => 'selesai']);
    Carbon::setTestNow('2026-11-19');

    $this->artisan('janji:pengingat');

    expect(ReminderLog::count())->toBe(0);
});

it('tombol kirim mencatat sent_at sekali dan membuka wa_link; hanya pemilik', function () {
    [$owner] = f5Aktif();
    Carbon::setTestNow('2026-11-19');
    $this->artisan('janji:pengingat');
    $log = ReminderLog::first();

    $this->actingAs($owner)->get('/kesepakatan')->assertSee('Perlu diingatkan');

    $this->actingAs(User::factory()->create())->post(route('pengingat.kirim', $log))->assertNotFound();
    expect($log->fresh()->sent_at)->toBeNull();

    $r = $this->actingAs($owner)->post(route('pengingat.kirim', $log));
    expect($r->headers->get('Location'))->toStartWith('https://wa.me/62812345678');
    $first = $log->fresh()->sent_at;
    expect($first)->not->toBeNull();

    Carbon::setTestNow('2026-11-19 12:00');
    $this->actingAs($owner)->post(route('pengingat.kirim', $log));
    expect($log->fresh()->sent_at->equalTo($first))->toBeTrue()->and(ReminderLog::count())->toBe(1);

    $this->actingAs($owner)->get('/kesepakatan')->assertDontSee('Perlu diingatkan');
});

it('driver sms dan whatsapp_api hanya mengantre job nonaktif tanpa memanggil layanan', function () {
    Queue::fake();
    Http::fake();

    foreach (['sms', 'whatsapp_api'] as $ch) {
        config(['janji.notifier' => $ch]);
        expect(app(ConfirmationNotifier::class)->send('0812', 'halo'))->toBeNull();
        Queue::assertPushed(SendNotification::class, fn ($j) => $j->channel === $ch);

        (new SendNotification($ch, '0812', 'halo'))->handle(); // nonaktif: tidak melempar, tidak mengirim
    }

    Http::assertNothingSent();
});
