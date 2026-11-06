<!-- Copyright (c) 2026 Vassel Goleyu. All rights reserved. -->
# Progress Janji Tukang

Aturan kerja: satu step per sesi. Di akhir step, perbarui file ini, jalankan tes, lalu berhenti dan tunggu persetujuan.

## Status step

| Step | Judul | Status |
|---|---|---|
| 1 | Desain | **Selesai, menunggu persetujuan** |
| 2 | Fondasi | Belum |
| 3 | Alur kesepakatan | Belum |
| 4 | Addendum | Belum |
| 5 | Termin dan bukti foto | Belum |
| 6 | Tautan konfirmasi, notifikasi, pengingat | Belum |
| 7 | Verifikasi dan dokumentasi | Belum |

## Step 1: Desain (selesai)

Berkas: [design.md](design.md), [assumptions.md](assumptions.md), `LICENSE`.
Tes: tidak ada (belum ada kode aplikasi). Pemeriksaan dilakukan lewat matriks keterlacakan di design.md bagian 12.

Keputusan penting di luar prompt (rinci di design.md bagian 11):
- D-01 termin hidup milik kesepakatan, rencana termin ada di snapshot versi.
- D-02 draf v1 = baris versi `draft` tanpa nomor.
- D-03 pembuat mengonfirmasi di dalam aplikasi, token hanya untuk tukang.
- D-04 tautan tidak bisa dilihat ulang, "salin tautan" menerbitkan token baru.
- D-05 satu versi terbuka per kesepakatan (generated column `open_slot`).
- D-13 tes memakai MySQL.
- D-17 paket tambahan: `intervention/image` v3, `laravel-lang/lang`.

## Catatan untuk Step 2

- Lingkungan lokal: PHP 8.4.8, Composer 2.8.9, klien MySQL 9.4. Server MySQL belum dipastikan berjalan. Periksa di awal Step 2.
- Versi Laravel "stabil terbaru" diverifikasi lewat Composer di Step 2.
- Kepemilikan (skill project-ownership): `LICENSE` sudah ada. Di Step 2 tambahkan `composer.json` (`authors`, `license-file`), `<meta name="author">` dan footer di layout. Header hak cipta di file kode yang dibuat. README di Step 7.
- Migration harus memuat CHECK constraint dan generated column `open_slot` sesuai design.md bagian 3.

## Pertanyaan untuk Anda (jawab sebelum Step 2 bila ingin mengubah pilihan saya)

1. D-08: v1 yang ditolak tukang membuat kesepakatan `dibatalkan` (pembuat membuat kesepakatan baru). Setuju?
2. D-15: konfirmasi bersifat final, tidak bisa ditarik. Setuju?
3. D-17: boleh menambah dua paket pihak ketiga (`intervention/image`, `laravel-lang/lang`)?
