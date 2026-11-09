<?php

namespace App\Models;

use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** menunggu -> bukti_lengkap (foto sebelum+sesudah) -> selesai (konfirmasi pemilik DAN tukang). */
class Termin extends Model
{
    use HasUlids;

    public const LABEL = ['menunggu' => 'Menunggu bukti', 'bukti_lengkap' => 'Bukti lengkap', 'selesai' => 'Selesai'];

    protected $guarded = [];

    protected $casts = ['amount' => 'integer', 'due_date' => 'date'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TerminPhoto::class)->orderBy('id');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(TerminConfirmation::class);
    }

    /** Konfirmasi pertama mengunci foto. */
    public function isLocked(): bool
    {
        return $this->confirmations()->exists();
    }

    /** Satu bentuk data untuk endpoint JSON dan fallback Blade. */
    public function toTimeline(): array
    {
        return [
            'id' => $this->ulid,
            'number' => $this->position,
            'title' => $this->title,
            'amount_label' => Rupiah::format($this->amount),
            'due_label' => $this->due_date->translatedFormat('j M Y'),
            'status' => $this->status,
            'status_label' => self::LABEL[$this->status] ?? $this->status,
            'url' => route('termin.show', $this),
            'confirmed' => $this->confirmations->pluck('party')->all(),
            'photos' => $this->photos->map(fn (TerminPhoto $p) => [
                'kind' => $p->kind,
                'url' => route('foto.tampil', $p),
                'at' => $p->created_at->translatedFormat('j M Y H:i'),
            ])->all(),
        ];
    }
}
