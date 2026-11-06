<?php

use App\Exceptions\AppendOnlyException;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\User;
use App\Models\VersionConfirmation;
use App\Support\Rupiah;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeAgreement(): Agreement
{
    return Agreement::create([
        'user_id' => User::factory()->create()->id,
        'creator_role' => 'pemilik',
        'title' => 'Keramik kamar mandi',
        'counterparty_name' => 'Pak Budi',
    ]);
}

function addVersion(Agreement $a, int $total = 1500000, ?string $reason = null): AgreementVersion
{
    return AgreementVersion::addTo($a, 'Pasang keramik 2x3 m', $total,
        [['title' => 'Uang muka', 'amount' => $total, 'due_date' => '2026-11-20']], $reason);
}

function confirm(AgreementVersion $v, string $party, string $decision = 'setuju'): void
{
    VersionConfirmation::create(['agreement_version_id' => $v->id, 'party' => $party, 'decision' => $decision]);
}

// I1
it('menampilkan rupiah dengan titik ribuan', function () {
    expect(Rupiah::format(1500000))->toBe('Rp 1.500.000')->and(Rupiah::format(0))->toBe('Rp 0');
});

it('menyimpan uang sebagai integer bigint unsigned', function () {
    $v = addVersion(makeAgreement(), 99999999999);
    expect($v->fresh()->total_amount)->toBeInt()->toBe(99999999999);
    expect(fn () => addVersion(makeAgreement(), -1))->toThrow(Exception::class);
});

// I2
it('menolak update dan delete versi', function () {
    $v = addVersion(makeAgreement());
    expect(fn () => $v->update(['scope' => 'diubah']))->toThrow(AppendOnlyException::class);
    expect(fn () => $v->delete())->toThrow(AppendOnlyException::class);
    expect($v->fresh()->scope)->toBe('Pasang keramik 2x3 m');
});

it('menolak update dan delete konfirmasi', function () {
    $v = addVersion(makeAgreement());
    confirm($v, 'pemilik');
    $c = $v->confirmations()->first();
    expect(fn () => $c->update(['decision' => 'tolak']))->toThrow(AppendOnlyException::class);
    expect(fn () => $c->delete())->toThrow(AppendOnlyException::class);
});

it('merantai hash dengan versi sebelumnya', function () {
    $a = makeAgreement();
    $v1 = addVersion($a);
    $v2 = addVersion($a, 2000000, 'Tambah luas');

    expect($v1->previous_hash)->toBeNull()
        ->and($v2->number)->toBe(2)
        ->and($v2->previous_hash)->toBe($v1->content_hash)
        ->and($v1->content_hash)->toHaveLength(64)
        ->and($v1->fresh()->hashIsValid())->toBeTrue()
        ->and($v2->fresh()->hashIsValid())->toBeTrue();
});

it('mendeteksi data yang diubah lewat SQL mentah', function () {
    $v = addVersion(makeAgreement());
    DB::table('agreement_versions')->where('id', $v->id)->update(['total_amount' => 1]);
    expect($v->fresh()->hashIsValid())->toBeFalse();
});

// I3
it('versi berlaku = versi tertinggi yang dikonfirmasi kedua pihak', function () {
    $a = makeAgreement();
    expect($a->effectiveVersion())->toBeNull();

    $v1 = addVersion($a);
    confirm($v1, 'pemilik');
    expect($a->effectiveVersion())->toBeNull();
    confirm($v1, 'tukang');
    expect($a->effectiveVersion()->id)->toBe($v1->id);

    $v2 = addVersion($a, 2000000, 'Tambah luas');
    confirm($v2, 'pemilik');
    expect($a->effectiveVersion()->id)->toBe($v1->id); // v2 menunggu, v1 tetap berlaku

    confirm($v2, 'tukang');
    expect($a->effectiveVersion()->id)->toBe($v2->id);
});

it('penolakan dicatat dan versi lama tetap berlaku', function () {
    $a = makeAgreement();
    $v1 = addVersion($a);
    confirm($v1, 'pemilik');
    confirm($v1, 'tukang');
    $v2 = addVersion($a, 3000000, 'Naik harga');
    confirm($v2, 'pemilik');
    confirm($v2, 'tukang', 'tolak');

    expect($a->effectiveVersion()->id)->toBe($v1->id)
        ->and(AgreementVersion::count())->toBe(2)
        ->and($v2->confirmations()->where('decision', 'tolak')->exists())->toBeTrue();
});
