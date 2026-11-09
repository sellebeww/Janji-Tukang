<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminPhoto extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $guarded = [];

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
}
