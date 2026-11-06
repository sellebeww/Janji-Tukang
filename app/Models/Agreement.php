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

    /** I3: versi tertinggi yang disetujui kedua pihak. Null bila belum ada. */
    public function effectiveVersion(): ?AgreementVersion
    {
        return $this->versions()->reorder('number', 'desc')
            ->whereHas('confirmations', fn ($q) => $q->where('party', 'pemilik')->where('decision', 'setuju'))
            ->whereHas('confirmations', fn ($q) => $q->where('party', 'tukang')->where('decision', 'setuju'))
            ->first();
    }
}
