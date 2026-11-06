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
