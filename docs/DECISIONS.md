# Keputusan

Format: tanggal, keputusan, alasan.

## 2026-11-06 Fase 0
- **Laravel 13.11.0** (versi stabil terbaru dari composer saat itu), PHP 8.4, MySQL 9.4 (Homebrew).
- **Starter kit: Laravel Breeze, stack Blade, tes Pest.** Starter kit resmi yang berbasis Blade. Auth tidak ditulis sendiri.
- **Bootstrap 5 untuk halaman aplikasi; halaman auth bawaan Breeze tetap Tailwind.** Menulis ulang view auth Breeze ke Bootstrap = menulis auth sendiri dan menambah risiko. Dua entry Vite terpisah supaya tiap halaman hanya memuat satu CSS. Tinjau ulang bila ingin satu sistem CSS.
- **Tes memakai MySQL (`janji_tukang_test`), bukan SQLite.** Kolom JSON, CHECK, dan perilaku bigint harus sama dengan produksi.
- **Vite dimatikan di `TestCase`.** Tes tidak boleh bergantung pada build aset.
- **Zona waktu Asia/Jakarta, locale `id`, faker `id_ID`.**
- **Dokumen desain lama dipindah ke `docs/_lama/`.** Ditulis untuk spec sebelumnya dan bertentangan dengan spec MVP sekarang. Tidak dipakai sebagai acuan.
- **Paket tambahan: belum ada.**

## Terbuka
- `npm install` gagal: `registry.npmjs.org` tidak terjangkau dari mesin ini (Packagist normal). Bootstrap, Sass, dan build Vite belum terpasang.

## 2026-11-06 Inti data (I1, I2, I3)
- **Status versi tidak disimpan.** Karena versi append-only, status diturunkan dari tabel `version_confirmations` (juga append-only, unik per versi+pihak). Versi berlaku = nomor tertinggi dengan `setuju` dari `pemilik` dan `tukang`.
- **Hash:** `sha256(JSON kanonik {installments, number, reason, scope, total_amount} + previous_hash)`. Kunci diurutkan alfabet. Perubahan lewat SQL mentah terdeteksi oleh `hashIsValid()`, tetapi tidak dicegah (tidak ada trigger DB).
- **Rencana termin disimpan di JSON versi** (`installments`). Tabel termin hidup (F2: foto, status) dibuat di fase berikutnya.
- **Pembuat mengonfirmasi lewat aplikasi, pihak lawan lewat tautan.** Keduanya menulis ke `version_confirmations`.
- **ULID** di kolom `ulid` untuk URL publik; `id` auto-increment tidak pernah dipakai di URL.
- Method penambah versi bernama `AgreementVersion::addTo()` (`append()` bentrok dengan Eloquent).

## 2026-11-06 Fase 2 (wizard, tautan konfirmasi, halaman pihak lawan)
- **docs/SPEC.md dan docs/ARCHITECTURE.md tidak ada di repo.** Acuan yang dipakai: spec MVP dari pesan pertama sesi ini.
- **Isi draf disimpan di kolom `agreements` (scope, total_amount, installments).** Versi append-only baru dibuat saat diajukan (v1). Setelah diajukan, draf terkunci oleh `AgreementPolicy::update`.
- **Status kesepakatan:** `draft` -> `menunggu_konfirmasi` -> `aktif`. Bila pihak lawan menekan "Ada yang perlu diubah": `perlu_diubah` (v1 tidak bisa diubah; jalur perubahan = addendum, Fase 4). Catatan alasan penolakan belum ada.
- **Pembuat otomatis "setuju" saat mengajukan** (konfirmasi pembuat tercatat dengan waktu, IP, user agent).
- **Tautan:** `ConfirmationLink` menerbitkan signed URL (kedaluwarsa = `expires_at` di DB, default 7 hari, `JANJI_TOKEN_TTL_DAYS`). `consume()` menandai `used_at` dengan UPDATE bersyarat di dalam transaksi + lock; bila pekerjaan gagal, `used_at` ikut batal. Target ada di baris DB, bukan di URL.
- **Tautan hanya tampil sekali** setelah dibuat (hanya hash yang disimpan). Pembuat membuat tautan baru bila perlu; tautan lama tetap berlaku sampai dipakai/kedaluwarsa (belum dicabut otomatis).
- **Rate limit:** 10/menit per IP (`JANJI_RATE_PER_MINUTE`) di GET dan POST `/k/{token}`; dipasang sebelum `signed` supaya tebakan signature ikut dihitung.
- **Bukan pemilik -> 404** (bukan 403) agar keberadaan data tidak bocor. Draf terkunci -> 403.
- **Notifier:** `wa_link` (default) dan `log`. Driver `sms`/`whatsapp_api` belum dibuat (bukan bagian Fase 2).
- **Uang di formulir:** `Rupiah::parse` hanya menerima angka bulat (opsional titik ribuan, awalan "Rp"). Desimal dan format ganjil ditolak, tidak ditebak.
- **Tanggal:** `translatedFormat('j M Y')`. Carbon locale `id` menulis Agustus sebagai "Agt".
- **Bootstrap sementara dari CDN (jsdelivr, v5.3.3, tanpa SRI)** di `layouts/janji.blade.php`, karena npm belum bisa dipakai. Ganti ke `@vite` setelah `npm install` jalan. Halaman pihak lawan bergantung pada CDN ini sampai itu.
- **Wizard tanpa JavaScript:** 6 baris termin tetap; baris kosong diabaikan.
- `Controller` dasar diberi trait `AuthorizesRequests` (Laravel 13 tidak menyertakannya).
