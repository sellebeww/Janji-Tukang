<?php

namespace App\Services;

use App\Exceptions\LinkException;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\Termin;
use App\Models\TerminConfirmation;
use App\Models\TerminPhoto;
use App\Models\User;
use App\Support\PhotoProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** menunggu <-> bukti_lengkap lewat foto; bukti_lengkap -> selesai lewat dua konfirmasi. Foto terkunci setelah konfirmasi pertama. */
class TerminFlow
{
    public const MAX_PHOTOS_PER_KIND = 10;

    /** Salin rencana versi berlaku menjadi termin hidup. Idempoten. */
    public function materialize(Agreement $agreement): void
    {
        DB::transaction(function () use ($agreement) {
            Agreement::whereKey($agreement->id)->lockForUpdate()->first();
            $agreement->refresh();
            if ($agreement->status !== 'aktif' || $agreement->termins()->exists() || ! ($version = $agreement->effectiveVersion())) {
                return;
            }
            foreach ($version->installments as $i => $row) {
                $agreement->termins()->create([
                    'agreement_version_id' => $version->id,
                    'position' => $i + 1,
                    'title' => $row['title'],
                    'amount' => $row['amount'],
                    'due_date' => $row['due_date'],
                ]);
            }
        });
    }

    /** Hal yang membuat rencana baru tidak bisa diterapkan ke termin hidup (termin terkunci/selesai berubah, atau dihapus padahal punya bukti). */
    public function conflicts(Agreement $agreement, array $installments): array
    {
        $out = [];
        foreach ($agreement->termins()->with('photos')->get() as $t) {
            $new = $installments[$t->position - 1] ?? null;
            $same = $new && $new['title'] === $t->title && (int) $new['amount'] === $t->amount && $new['due_date'] === $t->due_date->toDateString();
            if ($same) {
                continue;
            }
            if ($t->status === 'selesai' || $t->isLocked()) {
                $out[] = "Termin {$t->position} sudah ada konfirmasi, tidak bisa diubah atau dihapus.";
            } elseif (! $new && $t->photos->isNotEmpty()) {
                $out[] = "Termin {$t->position} sudah punya foto bukti, tidak bisa dihapus.";
            }
        }

        return $out;
    }

    /** Terapkan versi addendum yang baru berlaku ke termin hidup. Bukti yang sudah ada tidak dihapus. */
    public function apply(Agreement $agreement, AgreementVersion $version): void
    {
        if ($agreement->termins()->doesntExist()) {
            $this->materialize($agreement);

            return;
        }
        if ($this->conflicts($agreement, $version->installments)) {
            throw new LinkException('target'); // keadaan berubah sejak usulan dibuat; pembuat perlu mengusulkan ulang
        }
        $count = count($version->installments);
        $agreement->termins()->where('position', '>', $count)->delete();
        foreach ($version->installments as $i => $row) {
            $agreement->termins()->updateOrCreate(['position' => $i + 1], [
                'agreement_version_id' => $version->id,
                'title' => $row['title'], 'amount' => $row['amount'], 'due_date' => $row['due_date'],
            ]);
        }
    }

    public function addPhoto(Termin $termin, string $kind, UploadedFile $file, User $by): TerminPhoto
    {
        $img = PhotoProcessor::normalize($file->get()); // kerja berat di luar lock

        return DB::transaction(function () use ($termin, $kind, $img, $by) {
            $termin = $this->lock($termin);
            $this->assertEditable($termin);
            if ($termin->photos()->where('kind', $kind)->count() >= self::MAX_PHOTOS_PER_KIND) {
                throw ValidationException::withMessages(['foto' => 'Maksimal '.self::MAX_PHOTOS_PER_KIND.' foto per jenis.']);
            }

            $ulid = (string) Str::ulid();
            $path = "termin/{$termin->ulid}/{$ulid}.jpg";
            $photo = $termin->photos()->create([
                'ulid' => $ulid, 'kind' => $kind, 'path' => $path,
                'sha256' => hash('sha256', $img['bytes']), 'size' => strlen($img['bytes']),
                'width' => $img['width'], 'height' => $img['height'],
                'uploaded_by' => $by->id, 'created_at' => now(), // waktu server
            ]);
            Storage::disk('local')->put($path, $img['bytes']) ?: throw new \RuntimeException('Gagal menyimpan foto.');
            $this->refreshStatus($termin);

            return $photo;
        });
    }

    public function deletePhoto(TerminPhoto $photo): void
    {
        DB::transaction(function () use ($photo) {
            $termin = $this->lock($photo->termin);
            $this->assertEditable($termin);
            $photo->delete();
            Storage::disk('local')->delete($photo->path);
            $this->refreshStatus($termin);
        });
    }

    /** Tutup termin dari satu pihak. Syarat: bukti lengkap. Konfirmasi kedua -> selesai. */
    public function confirm(Termin $termin, string $party, ?string $ip, ?string $userAgent): void
    {
        DB::transaction(function () use ($termin, $party, $ip, $userAgent) {
            $termin = $this->lock($termin);
            if ($termin->agreement->status !== 'aktif' || $termin->status !== 'bukti_lengkap') {
                throw ValidationException::withMessages(['termin' => 'Termin belum bisa ditutup: foto sebelum dan sesudah harus ada.']);
            }
            if ($termin->confirmations()->where('party', $party)->exists()) {
                throw ValidationException::withMessages(['termin' => 'Konfirmasi Anda sudah tercatat.']);
            }

            TerminConfirmation::create([
                'termin_id' => $termin->id, 'party' => $party, 'ip' => $ip,
                'user_agent' => $userAgent ? Str::limit($userAgent, 255, '') : null,
            ]);
            if ($termin->confirmations()->count() === 2) {
                $termin->update(['status' => 'selesai']);
                if ($termin->agreement->termins()->where('status', '!=', 'selesai')->doesntExist()) {
                    $termin->agreement->update(['status' => 'selesai']);
                }
            }
        });
    }

    private function lock(Termin $termin): Termin
    {
        return Termin::with('agreement')->whereKey($termin->id)->lockForUpdate()->firstOrFail();
    }

    private function assertEditable(Termin $termin): void
    {
        if ($termin->agreement->status !== 'aktif') {
            throw ValidationException::withMessages(['foto' => 'Kesepakatan belum aktif.']);
        }
        if ($termin->status === 'selesai' || $termin->isLocked()) {
            throw ValidationException::withMessages(['foto' => 'Foto sudah dikunci karena ada konfirmasi.']);
        }
    }

    private function refreshStatus(Termin $termin): void
    {
        $kinds = $termin->photos()->reorder()->distinct()->pluck('kind');
        $termin->update(['status' => $kinds->count() === 2 ? 'bukti_lengkap' : 'menunggu']);
    }
}
