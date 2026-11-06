<?php

namespace App\Exceptions;

use RuntimeException;

class LinkException extends RuntimeException
{
    /** tidak_ditemukan | kedaluwarsa | dipakai | tujuan | target */
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
