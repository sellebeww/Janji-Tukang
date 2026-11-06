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
