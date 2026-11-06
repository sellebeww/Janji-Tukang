<?php

namespace App\Models;

use App\Exceptions\AppendOnlyException;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/** I2: append-only. Hash = bukti integritas data internal, bukan tanda tangan elektronik. */
class AgreementVersion extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

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

    protected static function booted(): void
    {
        static::updating(fn () => throw new AppendOnlyException('Versi kesepakatan tidak bisa diubah.'));
        static::deleting(fn () => throw new AppendOnlyException('Versi kesepakatan tidak bisa dihapus.'));
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(VersionConfirmation::class);
    }

    /** Tambah versi baru di akhir rantai. */
    public static function addTo(Agreement $agreement, string $scope, int $totalAmount, array $installments, ?string $reason = null): self
    {
        return DB::transaction(function () use ($agreement, $scope, $totalAmount, $installments, $reason) {
            Agreement::whereKey($agreement->id)->lockForUpdate()->first();
            $last = static::where('agreement_id', $agreement->id)->orderByDesc('number')->first();
            $number = ($last?->number ?? 0) + 1;

            $version = new static([
                'agreement_id' => $agreement->id,
                'number' => $number,
                'scope' => $scope,
                'total_amount' => $totalAmount,
                'installments' => $installments,
                'reason' => $reason,
                'previous_hash' => $last?->content_hash,
            ]);
            $version->content_hash = $version->computeHash();
            $version->save();

            return $version;
        });
    }

    /** sha256(konten kanonik + hash versi sebelumnya) */
    public function computeHash(): string
    {
        $content = [
            'installments' => array_map(fn ($i) => [
                'amount' => (int) $i['amount'],
                'due_date' => $i['due_date'] ?? null,
                'title' => $i['title'],
            ], $this->installments),
            'number' => (int) $this->number,
            'reason' => $this->reason,
            'scope' => $this->scope,
            'total_amount' => (int) $this->total_amount,
        ];

        return hash('sha256', json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).$this->previous_hash);
    }

    public function hashIsValid(): bool
    {
        return hash_equals($this->content_hash, $this->computeHash());
    }
}
