<?php

use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\User;
use App\Models\VersionConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;

require_once __DIR__.'/../Fase2/Helpers.php';

uses(RefreshDatabase::class);

function f4Aktif(): array
{
    $owner = User::factory()->create();
    [$a, $v] = f2Submitted($owner);
    test()->post(f2Url($v->id), ['decision' => 'setuju'])->assertOk();

    return [$owner, $a->fresh(), $v];
}

function f4Form(array $over = []): array
{
    return array_merge([
        'reason' => 'Pemilik minta keramik diganti ukuran besar',
        'scope' => 'Pasang keramik 60x60, 2x3 m',
        'total_amount' => '1.800.000',
        'termin' => [
            ['title' => 'Uang muka', 'amount' => '500.000', 'due_date' => '2026-11-20'],
            ['title' => 'Pelunasan', 'amount' => '1.300.000', 'due_date' => '2026-12-05'],
        ],
    ], $over);
}

function f4Propose(User $owner, Agreement $a, array $over = []): AgreementVersion
{
    test()->actingAs($owner)->post(route('addendum.store', $a), f4Form($over))->assertSessionHasNoErrors()->assertRedirect();

    return $a->versions()->reorder('number', 'desc')->first();
}

it('membuat versi addendum baru dengan alasan wajib dan tidak menyentuh versi lama', function () {
    [$owner, $a, $v1] = f4Aktif();
    $before = $v1->fresh()->getAttributes();

    $this->actingAs($owner)->post(route('addendum.store', $a), f4Form(['reason' => '']))->assertSessionHasErrors('reason');
    expect($a->versions()->count())->toBe(1);

    $v2 = f4Propose($owner, $a);

    expect($v2->number)->toBe(2)->and($v2->kind)->toBe('addendum')->and($v2->reason)->toContain('keramik')
        ->and($v1->fresh()->getAttributes())->toBe($before)
        ->and($v2->previous_hash)->toBe($v1->content_hash);
});

it('menolak addendum tanpa perubahan atau dari akun lain', function () {
    [$owner, $a] = f4Aktif();
    $same = f4Form(['scope' => 'Pasang keramik 2x3 m', 'total_amount' => '1.500.000', 'termin' => [
        ['title' => 'Uang muka', 'amount' => '500.000', 'due_date' => '2026-11-20'],
        ['title' => 'Pelunasan', 'amount' => '1.000.000', 'due_date' => '2026-12-05'],
    ]]);

    $this->actingAs($owner)->post(route('addendum.store', $a), $same)->assertSessionHasErrors('scope');
    $this->actingAs(User::factory()->create())->post(route('addendum.store', $a), f4Form())->assertNotFound();
    expect($a->versions()->count())->toBe(1);
});

it('menjaga versi berlaku di tiap tahap: satu pihak saja tidak cukup', function () {
    [$owner, $a, $v1] = f4Aktif();
    expect($a->effectiveVersion()->id)->toBe($v1->id);

    $v2 = f4Propose($owner, $a);
    // Tahap 1: pembuat sudah setuju, pihak lawan belum.
    expect($v2->confirmations()->pluck('party')->all())->toBe(['pemilik'])
        ->and($a->fresh()->effectiveVersion()->id)->toBe($v1->id)
        ->and($a->fresh()->status)->toBe('aktif')
        ->and($a->fresh()->termins()->first()->amount)->toBe(500000)
        ->and($a->fresh()->termins()->count())->toBe(2);
    $this->actingAs($owner)->get(route('agreements.show', $a))->assertOk()->assertSee('versi 1 tetap berlaku', false)->assertSee('Perubahan kesepakatan');

    // Tahap 2: pihak lawan setuju -> v2 berlaku, termin ikut.
    $this->post(f2Url($v2->id), ['decision' => 'setuju'])->assertOk()->assertSee('Perubahan kesepakatan sekarang berlaku');
    $a = $a->fresh();
    expect($a->effectiveVersion()->id)->toBe($v2->id)->and($a->status)->toBe('aktif')
        ->and($a->termins()->get()->pluck('amount')->all())->toBe([500000, 1300000]);
});

it('mencatat penolakan dan membiarkan versi lama berlaku', function () {
    [$owner, $a, $v1] = f4Aktif();
    $v2 = f4Propose($owner, $a);

    $this->post(f2Url($v2->id), ['decision' => 'tolak'])->assertOk()->assertSee('Kesepakatan yang lama tetap berlaku');

    expect(VersionConfirmation::where('agreement_version_id', $v2->id)->where('party', 'tukang')->value('decision'))->toBe('tolak')
        ->and($a->fresh()->effectiveVersion()->id)->toBe($v1->id)
        ->and($a->fresh()->status)->toBe('aktif')
        ->and($a->fresh()->pendingVersion())->toBeNull();
});

it('hanya usulan terbaru yang bisa dikonfirmasi; usulan baru menggantikan yang lama', function () {
    [$owner, $a] = f4Aktif();
    $v2 = f4Propose($owner, $a);
    $oldUrl = f2Url($v2->id);
    $v3 = f4Propose($owner, $a, ['total_amount' => '1.900.000', 'termin' => [
        ['title' => 'Uang muka', 'amount' => '500.000', 'due_date' => '2026-11-20'],
        ['title' => 'Pelunasan', 'amount' => '1.400.000', 'due_date' => '2026-12-05'],
    ]]);

    $this->post($oldUrl, ['decision' => 'setuju'])->assertStatus(410);
    expect($a->fresh()->effectiveVersion()->number)->toBe(1);

    $this->post(f2Url($v3->id), ['decision' => 'setuju'])->assertOk();
    expect($a->fresh()->effectiveVersion()->number)->toBe(3);
});

it('menolak perubahan yang menyentuh termin yang sudah dikonfirmasi', function () {
    [$owner, $a] = f4Aktif();
    $t = $a->termins()->first();
    \App\Models\TerminConfirmation::create(['termin_id' => $t->id, 'party' => 'pemilik']);

    $ubah = f4Form(['total_amount' => '1.500.000', 'termin' => [
        ['title' => 'Uang muka', 'amount' => '600.000', 'due_date' => '2026-11-20'],
        ['title' => 'Pelunasan', 'amount' => '900.000', 'due_date' => '2026-12-05'],
    ]]);
    $this->actingAs($owner)->post(route('addendum.store', $a), $ubah)->assertSessionHasErrors('termin');
    expect($a->versions()->count())->toBe(1);

    // Termin yang dikonfirmasi tidak berubah, termin lain boleh berubah.
    f4Propose($owner, $a);
    expect($a->versions()->count())->toBe(2);
});

it('addendum setelah v1 ditolak: v1 tidak pernah berlaku, v2 mengaktifkan kesepakatan', function () {
    $owner = User::factory()->create();
    [$a, $v1] = f2Submitted($owner);
    $this->post(f2Url($v1->id), ['decision' => 'tolak'])->assertOk();
    expect($a->fresh()->status)->toBe('perlu_diubah')->and($a->fresh()->effectiveVersion())->toBeNull();

    $v2 = f4Propose($owner, $a);
    expect($a->fresh()->status)->toBe('menunggu_konfirmasi')->and($a->fresh()->effectiveVersion())->toBeNull();

    $this->post(f2Url($v2->id), ['decision' => 'setuju'])->assertOk();
    expect($a->fresh()->status)->toBe('aktif')->and($a->fresh()->effectiveVersion()->id)->toBe($v2->id)
        ->and($a->fresh()->termins()->count())->toBe(2);
});

it('rantai hash tetap valid setelah addendum dan menangkap perubahan', function () {
    [$owner, $a, $v1] = f4Aktif();
    $v2 = f4Propose($owner, $a);
    $v3 = f4Propose($owner, $a, ['reason' => 'Ubah lagi', 'total_amount' => '1.700.000', 'termin' => [
        ['title' => 'Uang muka', 'amount' => '500.000', 'due_date' => '2026-11-20'],
        ['title' => 'Pelunasan', 'amount' => '1.200.000', 'due_date' => '2026-12-05'],
    ]]);

    $chain = $a->versions()->get();
    expect($chain->every->hashIsValid())->toBeTrue()
        ->and($chain[1]->previous_hash)->toBe($chain[0]->content_hash)
        ->and($chain[2]->previous_hash)->toBe($chain[1]->content_hash);

    \DB::table('agreement_versions')->where('id', $v2->id)->update(['total_amount' => 1]);
    expect($a->versions()->get()->map->hashIsValid()->all())->toBe([true, false, true]);
});

it('menampilkan riwayat dengan waktu, kode versi, dan perubahan', function () {
    [$owner, $a, $v1] = f4Aktif();
    $v2 = f4Propose($owner, $a);

    $this->actingAs($owner)->get(route('agreements.riwayat', $a))->assertOk()
        ->assertSee('Perubahan kesepakatan')->assertSee($v1->code())->assertSee($v2->code())
        ->assertSee('Rp 1.800.000', false)->assertSee('Pemilik minta keramik diganti')->assertSee('WIB');
    $this->actingAs(User::factory()->create())->get(route('agreements.riwayat', $a))->assertNotFound();
});

it('menampilkan kode versi di ringkasan dan hanya untuk versi yang berlaku', function () {
    [$owner, $a, $v1] = f4Aktif();
    f4Propose($owner, $a);

    $this->actingAs($owner)->get(route('agreements.show', $a))->assertSee('Kode versi')->assertSee($v1->code());
});

it('halaman pihak lawan menampilkan perubahan dan alasan', function () {
    [$owner, $a] = f4Aktif();
    $v2 = f4Propose($owner, $a);

    $this->get(f2Url($v2->id))->assertOk()->assertSee('Perubahan kesepakatan')->assertSee('Pemilik minta keramik diganti')
        ->assertSee('Rp 1.800.000', false)->assertSee('tetap berlaku');
});
