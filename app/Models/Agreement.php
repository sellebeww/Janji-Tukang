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

    public function termins(): HasMany
    {
        return $this->hasMany(Termin::class)->orderBy('position');
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

    /** Usulan yang masih ditunggu: versi terakhir, belum berlaku, belum ditolak. Usulan lebih baru menggantikan yang lama. */
    public function pendingVersion(): ?AgreementVersion
    {
        $last = $this->versions()->reorder('number', 'desc')->with('confirmations')->first();
        if (! $last || $last->confirmations->contains('decision', 'tolak')) {
            return null;
        }

        return $last->id === $this->effectiveVersion()?->id ? null : $last;
    }

    /** Riwayat: tiap versi dengan status berlaku | menunggu | ditolak | digantikan. */
    public function history(): array
    {
        $versions = $this->versions()->with('confirmations')->get();
        $effective = $this->effectiveVersion();
        $last = $versions->last();

        return $versions->map(function (AgreementVersion $v) use ($effective, $last) {
            $status = match (true) {
                $v->id === $effective?->id => 'berlaku',
                $v->confirmations->contains('decision', 'tolak') => 'ditolak',
                $v->id === $last->id => 'menunggu',
                default => 'digantikan',
            };
            return ['version' => $v, 'status' => $status];
        })->all();
    }

    /** Data untuk halaman ringkasan: versi yang diminta; bila tidak ada, versi berlaku, lalu versi terakhir, lalu draf. */
    public function summary(?AgreementVersion $version = null): array
    {
        $src = $version ?? $this->effectiveVersion() ?? $this->versions()->reorder('number', 'desc')->first() ?? $this;

        return [
            'title' => $this->title,
            'creator_name' => $this->user->name,
            'creator_role' => $this->creator_role,
            'counterparty_name' => $this->counterparty_name,
            'scope' => $src->scope,
            'total' => $src->total_amount,
            'installments' => $src->installments ?? [],
            'version_no' => $src instanceof AgreementVersion ? $src->number : null,
            'version_code' => $src instanceof AgreementVersion ? $src->code() : null,
            'status' => $this->status,
        ];
    }
}
