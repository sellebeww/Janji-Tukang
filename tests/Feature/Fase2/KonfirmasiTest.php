<?php

use App\Exceptions\LinkException;
use App\Models\Agreement;
use App\Models\ConfirmationToken;
use App\Models\User;
use App\Models\VersionConfirmation;
use App\Services\ConfirmationLink;
use Illuminate\Foundation\Testing\RefreshDatabase;

require_once __DIR__.'/Helpers.php';

uses(RefreshDatabase::class);

it('menampilkan ringkasan lewat tautan valid, berulang kali, tanpa menghabiskannya', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);

    $this->get($url)->assertOk()->assertSee('Keramik kamar mandi')->assertSee('Rp 1.500.000')
        ->assertSee('Saya setuju')->assertSee('Ada yang perlu diubah')
        ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
    $this->get($url)->assertOk();
    expect(ConfirmationToken::first()->used_at)->toBeNull();
});

it('hanya menyimpan hash token', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);
    $raw = basename(parse_url($url, PHP_URL_PATH));

    $row = ConfirmationToken::first();
    expect($row->token_hash)->toBe(hash('sha256', $raw))->and(json_encode($row->getAttributes()))->not->toContain($raw);
});

it('menolak signature yang dirusak', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);

    $this->get(preg_replace('/signature=[a-f0-9]+/', 'signature=deadbeef', $url))->assertForbidden();
    $this->get(str_replace('expires=', 'expires=9', $url))->assertForbidden();
    $this->post(preg_replace('/signature=[a-f0-9]+/', 'signature=deadbeef', $url), ['decision' => 'setuju'])->assertForbidden();
    expect(VersionConfirmation::where('party', 'tukang')->count())->toBe(0);
});

it('menolak token lain meski signature URL-nya valid', function () {
    [$a, $v] = f2Submitted();
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('konfirmasi.show', now()->addDay(), ['token' => 'tidak-ada-di-db']);

    $this->get($url)->assertNotFound();
});

it('menolak tautan kedaluwarsa', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);

    $this->travel(8)->days();

    $this->get($url)->assertForbidden(); // signature kedaluwarsa
    $this->post($url, ['decision' => 'setuju'])->assertForbidden();
    expect($a->fresh()->status)->toBe('menunggu_konfirmasi');
});

it('menolak tautan yang kedaluwarsa di DB walau signature masih berlaku', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);
    ConfirmationToken::query()->update(['expires_at' => now()->subMinute()]);

    $this->get($url)->assertStatus(410)->assertSee('kedaluwarsa');
    $this->post($url, ['decision' => 'setuju'])->assertStatus(410);
    expect($a->fresh()->status)->toBe('menunggu_konfirmasi');
});

it('konfirmasi setuju mencatat I8 dan mengaktifkan kesepakatan', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);

    $this->withHeaders(['User-Agent' => 'HP-Pak-Budi/1.0'])->post($url, ['decision' => 'setuju'])
        ->assertOk()->assertSee('Terima kasih');

    $c = VersionConfirmation::where('party', 'tukang')->firstOrFail();
    expect($c->decision)->toBe('setuju')->and($c->ip)->not->toBeNull()
        ->and($c->user_agent)->toBe('HP-Pak-Budi/1.0')->and($c->created_at)->not->toBeNull()
        ->and($a->fresh()->status)->toBe('aktif')
        ->and($a->fresh()->effectiveVersion()->id)->toBe($v->id);
});

it('tautan tidak bisa dipakai ulang', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);

    $this->post($url, ['decision' => 'setuju'])->assertOk();
    $this->post($url, ['decision' => 'tolak'])->assertStatus(410)->assertSee('sudah dipakai');
    $this->get($url)->assertStatus(410);

    expect(VersionConfirmation::where('party', 'tukang')->count())->toBe(1)->and($a->fresh()->status)->toBe('aktif');
});

it('dua konfirmasi berurutan dengan dua tautan berbeda: hanya satu berhasil', function () {
    [$a, $v] = f2Submitted();
    $url1 = f2Url($v->id);
    $url2 = f2Url($v->id);

    $this->post($url1, ['decision' => 'setuju'])->assertOk();
    $this->post($url2, ['decision' => 'setuju'])->assertStatus(410); // kesepakatan tak lagi menunggu

    expect(VersionConfirmation::where('party', 'tukang')->count())->toBe(1);
});

it('dua pemakaian berurutan token yang sama lewat service: kedua gagal', function () {
    [$a, $v] = f2Submitted();
    $links = app(ConfirmationLink::class);
    $raw = basename(parse_url(f2Url($v->id), PHP_URL_PATH));
    $n = 0;

    $links->consume($raw, ConfirmationLink::KONFIRMASI_VERSI, function () use (&$n) { $n++; });
    expect(fn () => $links->consume($raw, ConfirmationLink::KONFIRMASI_VERSI, function () use (&$n) { $n++; }))
        ->toThrow(LinkException::class);
    expect($n)->toBe(1);
});

it('membatalkan used_at bila pekerjaan gagal', function () {
    [$a, $v] = f2Submitted();
    $links = app(ConfirmationLink::class);
    $raw = basename(parse_url(f2Url($v->id), PHP_URL_PATH));

    expect(fn () => $links->consume($raw, ConfirmationLink::KONFIRMASI_VERSI, fn () => throw new RuntimeException('gagal')))
        ->toThrow(RuntimeException::class);
    expect(ConfirmationToken::first()->used_at)->toBeNull();
});

it('menolak tujuan atau target yang salah', function () {
    [$a, $v] = f2Submitted();
    $links = app(ConfirmationLink::class);
    $raw = basename(parse_url(f2Url($v->id), PHP_URL_PATH));

    $reason = fn (callable $f) => rescue(fn () => $f() && null, fn ($e) => $e instanceof LinkException ? $e->reason : 'lain', false);

    expect($reason(fn () => $links->resolve($raw, 'konfirmasi_termin')))->toBe('tujuan');
    expect($reason(fn () => $links->resolve($raw, ConfirmationLink::KONFIRMASI_VERSI, $v->id + 99)))->toBe('target');
    expect($reason(fn () => $links->consume($raw, 'konfirmasi_termin', fn () => 1)))->toBe('tujuan');
    expect(ConfirmationToken::first()->used_at)->toBeNull();
});

it('tautan versi A tidak bisa mengonfirmasi kesepakatan lain', function () {
    [$a1, $v1] = f2Submitted();
    [$a2, $v2] = f2Submitted();
    $url1 = f2Url($v1->id);

    $this->post($url1, ['decision' => 'setuju'])->assertOk();

    expect($a1->fresh()->status)->toBe('aktif')->and($a2->fresh()->status)->toBe('menunggu_konfirmasi');
});

it('"Ada yang perlu diubah" dicatat dan tidak mengaktifkan', function () {
    [$a, $v] = f2Submitted();

    $this->post(f2Url($v->id), ['decision' => 'tolak'])->assertOk()->assertSee('perubahan');

    expect($a->fresh()->status)->toBe('perlu_diubah')->and($a->fresh()->effectiveVersion())->toBeNull()
        ->and(VersionConfirmation::where('decision', 'tolak')->count())->toBe(1);
});

it('keputusan selain setuju/tolak ditolak dan tautan tidak terpakai', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);

    $this->post($url, ['decision' => 'apa-saja'])->assertSessionHasErrors('decision');
    expect(ConfirmationToken::first()->used_at)->toBeNull();
});

it('membatasi laju permintaan (rate limit)', function () {
    [$a, $v] = f2Submitted();
    $url = f2Url($v->id);

    foreach (range(1, config('janji.rate_per_minute')) as $_) {
        $this->get($url)->assertOk();
    }
    $this->get($url)->assertStatus(429);
});

it('rate limit juga menghitung tebakan signature', function () {
    foreach (range(1, config('janji.rate_per_minute')) as $_) {
        $this->get('/k/tebakan?expires=1&signature=x')->assertForbidden();
    }
    $this->get('/k/tebakan?expires=1&signature=x')->assertStatus(429);
});

it('halaman pihak lawan tidak memuat data pengguna lain', function () {
    [$a1, $v1] = f2Submitted(User::factory()->create(['email' => 'rahasia@contoh.test']));
    [$a2, $v2] = f2Submitted();
    $a2->update(['title' => 'Proyek Rahasia Lain']);

    $this->get(f2Url($v1->id))->assertOk()->assertDontSee('Proyek Rahasia Lain')->assertDontSee('rahasia@contoh.test');
});
