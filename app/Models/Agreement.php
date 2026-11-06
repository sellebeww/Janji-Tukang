<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agreement extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = ['installments' => 'array', 'total_amount' => 'integer'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AgreementVersion::class)->orderBy('number');
    }

    public function counterpartyRole(): string
    {
        return $this->creator_role === 'pemilik' ? 'tukang' : 'pemilik';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /** I3: versi tertinggi yang disetujui kedua pihak. Null bila belum ada. */
    public function effectiveVersion(): ?AgreementVersion
    {
        return $this->versions()->reorder('number', 'desc')
            ->whereHas('confirmations', fn ($q) => $q->where('party', 'pemilik')->where('decision', 'setuju'))
            ->whereHas('confirmations', fn ($q) => $q->where('party', 'tukang')->where('decision', 'setuju'))
            ->first();
    }

    /** Data untuk halaman ringkasan: dari versi terakhir bila sudah diajukan, dari draf bila belum. */
    public function summary(): array
    {
        $src = $this->versions()->reorder('number', 'desc')->first() ?? $this;

        return [
            'title' => $this->title,
            'creator_name' => $this->user->name,
            'creator_role' => $this->creator_role,
            'counterparty_name' => $this->counterparty_name,
            'scope' => $src->scope,
            'total' => $src->total_amount,
            'installments' => $src->installments ?? [],
            'version_no' => $src instanceof AgreementVersion ? $src->number : null,
            'status' => $this->status,
        ];
    }
}
