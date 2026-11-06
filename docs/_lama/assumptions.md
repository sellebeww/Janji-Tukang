<!-- Copyright (c) 2026 Vassel Goleyu. All rights reserved. -->
# Asumsi Janji Tukang (MVP)

Dokumen ini mencatat asumsi default. Asumsi baru yang muncul di step berikutnya ditambahkan di bagian paling bawah dengan penanda step-nya.

## A. Produk dan pengguna

| # | Asumsi | Akibat pada desain |
|---|--------|--------------------|
| A1 | **Pembuat** (pemilik rumah, kos, atau kontrakan) punya akun dan login. | Tabel `users` hanya berisi pembuat. Tidak ada peran admin pada MVP. |
| A2 | **Pihak lain** (tukang/mandor) **tidak punya akun**. Ia hanya menerima tautan konfirmasi. | Seluruh aksi tukang berjalan lewat token sekali pakai. Tukang tidak bisa mengunggah foto, membuat addendum, atau melihat daftar kesepakatan. |
| A3 | Satu kesepakatan punya tepat satu pembuat dan satu pihak lain. | `agreements.user_id` dan `counterparty_*` di tabel yang sama. Tidak ada tabel pihak. |
| A4 | Aplikasi adalah **catatan** kesepakatan yang disetujui kedua pihak. Bukan marketplace dan tidak memproses pembayaran. | Tidak ada pencarian tukang, profil publik, rating, komisi, atau gateway pembayaran. Nominal dan jatuh tempo hanya dicatat. |
| A5 | Tidak ada teks yang menyebut kesepakatan "sah" atau "mengikat secara hukum". | Semua teks pengguna memakai frasa "catatan kesepakatan yang disetujui kedua pihak". Tes di Step 7 memeriksa kata terlarang di `resources/views` dan `lang`. |
| A6 | Tidak ada panggilan API LLM/AI. "Dirapikan" berarti data terstruktur dirender ke ringkasan satu halaman dengan template Blade. | Tidak ada dependensi SDK AI. |
| A7 | Tukang meminta perubahan di luar aplikasi (lisan atau chat). Pembuat yang mencatatnya sebagai addendum, lalu tukang menyetujui lewat tautan. | Hanya pembuat yang bisa membuat addendum. |
| A8 | Satu kesepakatan hanya punya satu versi "terbuka" (draf atau menunggu persetujuan) pada satu waktu. | Dijaga dengan generated column + unique index (lihat design.md, D-05). |

## B. Identitas dan bukti

| # | Asumsi | Akibat |
|---|--------|--------|
| B1 | Identitas tukang = **kepemilikan tautan + nama yang diketik**. Tidak ada verifikasi KTP/OTP. | Ini bukti yang lemah dibanding tanda tangan terverifikasi. Aplikasi menyimpan waktu, IP, user agent, nama, versi teks persetujuan, dan hash snapshot sebagai bukti pendukung, bukan bukti mutlak. Tercatat sebagai batasan MVP di README (Step 7). |
| B2 | Pembuat mengonfirmasi **di dalam aplikasi** (sudah login), dengan data bukti yang sama seperti tukang. Tautan token hanya dibuat untuk tukang. | `confirmations.party = pembuat` terhubung ke `user_id`, bukan ke token. |
| B3 | Nomor telepon (pembuat dan tukang) disimpan **hanya untuk notifikasi**. | Tidak ada NIK, nomor rekening, atau data identitas lain. `notification_logs` hanya menyimpan nomor tersamar. |
| B4 | Alamat IP dan user agent dicatat sebagai bukti konfirmasi. | Dicantumkan di teks persetujuan agar pengguna tahu. Proxy tepercaya diatur lewat konfigurasi saat deploy. |
| B5 | Foto diunggah **oleh pembuat** setelah ia mengambilnya (atau menerimanya dari tukang). | Tukang tidak mengunggah foto pada MVP. |

## C. Uang dan tanggal

| # | Asumsi |
|---|--------|
| C1 | Mata uang rupiah, bilangan bulat tanpa desimal, disimpan `BIGINT UNSIGNED`. Batas atas 99.999.999.999. |
| C2 | Jatuh tempo adalah tanggal (tanpa jam) pada zona waktu `Asia/Jakarta`. "Besok" dan "lewat jatuh tempo" dihitung pada zona itu. |
| C3 | Pembayaran terjadi di luar aplikasi. Status termin `selesai` berarti kedua pihak mengonfirmasi bukti pekerjaan, bukan bahwa uang sudah berpindah. |

## D. Batasan MVP yang disengaja

| # | Batasan |
|---|---------|
| D1 | Kesepakatan yang sudah `aktif` tidak bisa dibatalkan dari aplikasi. Perubahan lewat addendum. |
| D2 | Kesepakatan dan versi yang sudah pernah diajukan tidak bisa dihapus (FK `RESTRICT`, model menolak delete). Satu-satunya pengecualian adalah draf yang belum pernah diajukan (design.md D-19). Penghapusan akun/data belum ada dan masuk daftar "Ke depan". |
| D3 | Satu tukang per kesepakatan, tanpa pembagian kerja antar tukang. |
| D4 | Tidak ada komentar, chat, atau lampiran selain foto sebelum/sesudah. |
| D5 | Notifikasi WhatsApp dan SMS hanya placeholder. Pengembangan dan tes memakai `LogChannel`. |
| D6 | Bahasa antarmuka hanya Bahasa Indonesia (`app.locale = id`). |

## E. Asumsi teknis

| # | Asumsi |
|---|--------|
| E1 | Laravel versi stabil terbaru saat Step 2 (diverifikasi lewat Composer), PHP 8.4, MySQL 8+. |
| E2 | Queue memakai driver `database` (MySQL). Scheduler dijalankan lewat `schedule:run` setiap menit di server. |
| E3 | Tes berjalan di MySQL terpisah (`janji_tukang_test`), bukan SQLite, agar kolom JSON, generated column, dan CHECK constraint berperilaku sama dengan produksi. |

## F. Asumsi tambahan per step

_(Belum ada. Tambahkan di sini beserta penanda step.)_
