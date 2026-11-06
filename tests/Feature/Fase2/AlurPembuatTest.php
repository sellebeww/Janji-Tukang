<?php

use App\Models\Agreement;
use App\Models\User;
use App\Notifiers\ConfirmationNotifier;
use App\Notifiers\LogNotifier;
use App\Notifiers\WaLinkNotifier;
use App\Support\Rupiah;
use Illuminate\Foundation\Testing\RefreshDatabase;

require_once __DIR__.'/Helpers.php';

uses(RefreshDatabase::class);

function f2Lingkup(array $o = []): array
{
    return $o + ['creator_role' => 'pemilik', 'title' => 'Cat pagar', 'counterparty_name' => 'Pak Joko',
        'counterparty_phone' => '0812-3456-7890', 'scope' => 'Cat ulang pagar depan'];
}

it('wizard 3 langkah sampai ringkasan, ajukan, tautan, konfirmasi, aktif (end-to-end)', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post('/kesepakatan', f2Lingkup())->assertRedirect();
    $a = Agreement::firstOrFail();
    expect($a->status)->toBe('draft');

    $this->put("/kesepakatan/{$a->ulid}/harga", ['total_amount' => 'Rp 1.500.000'])->assertRedirect(route('wizard.termin', $a));
    $this->put("/kesepakatan/{$a->ulid}/termin", ['termin' => [
        ['title' => 'Uang muka', 'amount' => '500.000', 'due_date' => '2026-08-17'],
        ['title' => 'Pelunasan', 'amount' => '1.000.000', 'due_date' => '2026-09-01'],
        ['title' => '', 'amount' => '', 'due_date' => ''],
    ]])->assertRedirect(route('agreements.show', $a));

    $this->get("/kesepakatan/{$a->ulid}")->assertOk()->assertSee('Rp 1.500.000')->assertSee('17 Agt 2026')->assertSee('Draf');

    $this->post("/kesepakatan/{$a->ulid}/ajukan")->assertRedirect();
    expect($a->fresh()->status)->toBe('menunggu_konfirmasi')->and($a->versions)->toHaveCount(1);

    $resp = $this->post("/kesepakatan/{$a->ulid}/tautan")->assertRedirect();
    $url = session('tautan');
    expect($url)->toContain('/k/')->and(session('wa'))->toStartWith('https://wa.me/6281234567890?text=');

    auth()->logout();
    $this->post($url, ['decision' => 'setuju'])->assertOk();
    expect($a->fresh()->status)->toBe('aktif');
});

it('menolak total desimal, nol, atau format aneh', function (string $input) {
    $this->actingAs($u = User::factory()->create());
    $a = f2Draft($u);

    $this->put("/kesepakatan/{$a->ulid}/harga", ['total_amount' => $input])->assertSessionHasErrors('total_amount');
    expect($a->fresh()->total_amount)->toBeNull();
})->with(['1500000.5', '1.500.000,50', '0', '-5', 'abc', '']);

function f2Draft(User $u, array $o = []): Agreement
{
    return Agreement::create($o + f2Lingkup() + ['user_id' => $u->id]);
}

it('menolak jumlah termin yang tidak sama dengan total', function () {
    $this->actingAs($u = User::factory()->create());
    $a = f2Draft($u, ['total_amount' => 1500000]);

    $this->put("/kesepakatan/{$a->ulid}/termin", ['termin' => [['title' => 'X', 'amount' => '1.000.000', 'due_date' => '2026-08-17']]])
        ->assertSessionHasErrors('termin');
    $this->put("/kesepakatan/{$a->ulid}/termin", ['termin' => []])->assertSessionHasErrors('termin');
    expect($a->fresh()->installments)->toBeNull();
});

it('tidak bisa mengajukan draf yang belum lengkap', function () {
    $this->actingAs($u = User::factory()->create());
    $a = f2Draft($u);

    $this->post("/kesepakatan/{$a->ulid}/ajukan")->assertSessionHasErrors();
    expect($a->fresh()->status)->toBe('draft')->and($a->versions)->toHaveCount(0);
});

it('draf terkunci setelah diajukan', function () {
    [$a] = f2Submitted($u = User::factory()->create());
    $this->actingAs($u);

    $this->put("/kesepakatan/{$a->ulid}/harga", ['total_amount' => '1'])->assertForbidden();
    $this->post("/kesepakatan/{$a->ulid}/ajukan")->assertForbidden();
});

it('ringkasan tidak bocor ke pengguna lain atau tamu', function () {
    [$a] = f2Submitted($pemilik = User::factory()->create());
    $lain = User::factory()->create();

    $this->get("/kesepakatan/{$a->ulid}")->assertRedirect('/login');

    $this->actingAs($lain);
    $this->get("/kesepakatan/{$a->ulid}")->assertNotFound();
    $this->get("/kesepakatan/{$a->ulid}/lingkup")->assertNotFound();
    $this->put("/kesepakatan/{$a->ulid}/harga", ['total_amount' => '1'])->assertNotFound();
    $this->post("/kesepakatan/{$a->ulid}/ajukan")->assertNotFound();
    $this->post("/kesepakatan/{$a->ulid}/tautan")->assertNotFound();
    $this->get('/kesepakatan')->assertOk()->assertDontSee('Keramik kamar mandi');
});

it('URL memakai ULID, bukan id', function () {
    [$a] = f2Submitted($u = User::factory()->create());
    $this->actingAs($u);

    expect(route('agreements.show', $a))->toContain($a->ulid)->and($a->ulid)->toHaveLength(26);
    $this->get('/kesepakatan/'.$a->id)->assertNotFound();
});

it('tautan konfirmasi hanya untuk kesepakatan yang menunggu', function () {
    $this->actingAs($u = User::factory()->create());
    $a = f2Draft($u);

    $this->post("/kesepakatan/{$a->ulid}/tautan")->assertForbidden();
});

it('notifier wa_link membuat tautan wa.me dan driver log tidak', function () {
    $wa = (new WaLinkNotifier)->send('0812-3456-7890', 'Halo & selamat');
    expect($wa)->toBe('https://wa.me/6281234567890?text=Halo%20%26%20selamat');

    config(['janji.notifier' => 'log']);
    expect(app(ConfirmationNotifier::class))->toBeInstanceOf(LogNotifier::class);
    expect(app(ConfirmationNotifier::class)->send('0812', 'x'))->toBeNull();

    config(['janji.notifier' => 'wa_link']);
    expect(app(ConfirmationNotifier::class))->toBeInstanceOf(WaLinkNotifier::class);
});

it('mengurai rupiah dengan ketat', function () {
    expect(Rupiah::parse('1.500.000'))->toBe(1500000)->and(Rupiah::parse('Rp 1500000'))->toBe(1500000)
        ->and(Rupiah::parse('1.500'))->toBe(1500)
        ->and(Rupiah::parse('1500000.5'))->toBeNull()->and(Rupiah::parse('1,5'))->toBeNull()->and(Rupiah::parse(''))->toBeNull();
});

it('view tidak memuat klaim hukum terlarang (I7)', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    foreach ($files as $f) {
        if ($f->isFile()) {
            expect(file_get_contents($f->getPathname()))
                ->not->toMatch('/\bsah\b|mengikat|tanda tangan elektronik|e-?sign/i', $f->getPathname());
        }
    }
});
