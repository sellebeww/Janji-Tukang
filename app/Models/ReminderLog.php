<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderLog extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $table = 'reminder_log';

    protected $guarded = [];

    protected $casts = ['sent_at' => 'datetime', 'created_at' => 'datetime'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function termin(): BelongsTo
    {
        return $this->belongsTo(Termin::class);
    }

    public function text(): string
    {
        $t = $this->termin;
        $a = $t->agreement;

        return sprintf(
            'Halo %s, mengingatkan soal pekerjaan "%s": termin %d "%s" sebesar %s %s (%s). Terima kasih. - %s',
            $a->counterparty_name, $a->title, $t->position, $t->title, \App\Support\Rupiah::format($t->amount),
            $this->kind === 'lewat' ? 'sudah lewat jatuh tempo' : 'jatuh tempo', $t->due_date->translatedFormat('j M Y'), $a->user->name,
        );
    }
}
