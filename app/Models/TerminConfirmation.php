<?php

namespace App\Models;

use App\Exceptions\AppendOnlyException;
use Illuminate\Database\Eloquent\Model;

class TerminConfirmation extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new AppendOnlyException('Konfirmasi tidak bisa diubah.'));
        static::deleting(fn () => throw new AppendOnlyException('Konfirmasi tidak bisa dihapus.'));
    }
}
