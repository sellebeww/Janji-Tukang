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
                'kind' => $reason === null ? 'awal' : 'addendum', // addendum <=> ada alasan (alasan ikut di-hash)
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

    /** Kode versi pendek untuk ditampilkan ke pengguna. */
    public function code(): string
    {
        return strtoupper(substr($this->content_hash, 0, 8));
    }

    /** Perubahan lingkup/harga/termin dibanding $old (null = tidak ada pembanding). */
    public function diffFrom(?self $old): array
    {
        if (! $old) {
            return ['scope' => null, 'total' => null, 'installments' => []];
        }
        $rows = [];
        $a = $old->installments;
        $b = $this->installments;
        foreach (range(0, max(count($a), count($b)) - 1) as $i) {
            $x = $a[$i] ?? null;
            $y = $b[$i] ?? null;
            if ($x === $y) {
                continue;
            }
            $rows[] = ['no' => $i + 1, 'type' => ! $x ? 'tambah' : (! $y ? 'hapus' : 'ubah'), 'old' => $x, 'new' => $y];
        }

        return [
            'scope' => $old->scope !== $this->scope ? [$old->scope, $this->scope] : null,
            'total' => $old->total_amount !== $this->total_amount ? [$old->total_amount, $this->total_amount] : null,
            'installments' => $rows,
        ];
    }

    public function hashIsValid(): bool
    {
        return hash_equals($this->content_hash, $this->computeHash());
    }
}
