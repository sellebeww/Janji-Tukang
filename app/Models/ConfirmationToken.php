<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfirmationToken extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];
}
