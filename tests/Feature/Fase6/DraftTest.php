<?php

use App\Drafting\AgreementDrafter;
use App\Drafting\DraftException;
use App\Drafting\LlmDrafter;
use App\Drafting\ManualDrafter;
use App\Models\Agreement;
use App\Models\User;
use App\Transcription\FakeTranscriber;
use App\Transcription\Transcriber;
use Illuminate\Support\Facades\Http;

function f6Llm(string $text): void
{
    config(['janji.drafter' => 'llm', 'janji.llm.api_key' => 'k']);
    Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => $text]]])]);
}

it('driver default manual; llm tanpa kunci jatuh ke manual', function () {
    expect(app(AgreementDrafter::class))->toBeInstanceOf(ManualDrafter::class);
    config(['janji.drafter' => 'llm', 'janji.llm.api_key' => null]);
    expect(app(AgreementDrafter::class))->toBeInstanceOf(ManualDrafter::class);
});

it('keluaran llm yang valid menjadi draf, tidak tersimpan otomatis', function () {
    f6Llm("```json\n".json_encode([
        'title' => 'Keramik', 'scope' => 'Pasang keramik', 'total_amount' => 1500000,
        'installments' => [['title' => 'DP', 'amount' => 500000, 'due_date' => null]],
    ])."\n```");

    $this->actingAs(User::factory()->create())->post(route('draft.generate'), ['story' => 'keramik 1,5 juta'])
        ->assertOk()->assertSee('belum tersimpan')->assertSee('Keramik')->assertSee('1.500.000');

    expect(Agreement::count())->toBe(0);
});

it('keluaran llm tidak valid ditolak, tidak ada draf, tidak ada data tersimpan', function (string $bad) {
    f6Llm($bad);

    $this->actingAs(User::factory()->create())->post(route('draft.generate'), ['story' => 'x'])
        ->assertSessionHasErrors('story');
    expect(Agreement::count())->toBe(0);
})->with(['bukan json', '{"title":"a"}', '{"title":"a","scope":"b","installments":[{"title":"t","amount":-5}]}']);

it('parse menolak jumlah termin lebih dari 6', function () {
    $rows = array_fill(0, 7, ['title' => 't', 'amount' => 1, 'due_date' => null]);
    expect(fn () => LlmDrafter::parse(json_encode(['title' => 'a', 'scope' => 'b', 'installments' => $rows])))->toThrow(DraftException::class);
});

it('baru tersimpan saat pengguna menekan simpan, lewat validasi yang sama dengan wizard', function () {
    $u = User::factory()->create();
    $form = ['creator_role' => 'pemilik', 'title' => 'Keramik', 'counterparty_name' => 'Pak Budi', 'scope' => 'x', 'total_amount' => '1.000.000'];

    $this->actingAs($u)->post(route('draft.simpan'), $form + ['termin' => [['title' => 'DP', 'amount' => '400.000', 'due_date' => '2026-12-01']]])
        ->assertSessionHasErrors('termin'); // jumlah termin != total
    expect(Agreement::count())->toBe(0);

    $this->actingAs($u)->post(route('draft.simpan'), $form + ['termin' => [['title' => 'Lunas', 'amount' => '1.000.000', 'due_date' => '2026-12-01']]])
        ->assertRedirect();
    expect(Agreement::first()->status)->toBe('draft');
});

it('transcriber hanya fake', function () {
    expect(app(Transcriber::class))->toBeInstanceOf(FakeTranscriber::class)
        ->and(app(Transcriber::class)->transcribe('/x.m4a'))->toBeString()->not->toBeEmpty();
});
