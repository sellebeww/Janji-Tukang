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

## 2026-11-06 Fase 3 (termin hidup, foto bukti, penutupan termin, timeline)
- **docs/SPEC.md dan docs/ARCHITECTURE.md tetap tidak ada.** I6 dan state machine termin diambil dari daftar tugas Fase 3 dan `docs/_lama/design.md` bagian 5.3 (hanya state machine termin). Cocokkan dengan spec asli bila ada.
- **"CRUD termin" = buat, baca, ubah status; tidak ada ubah/hapus isi.** Termin hidup (`termins`) disalin dari versi berlaku saat kedua pihak setuju (`TerminFlow::materialize`, idempoten, juga dipanggil saat halaman dibuka untuk kesepakatan aktif lama). Judul/nominal/jatuh tempo tidak bisa diubah di sini karena itu mengubah kesepakatan sepihak (I2/I3); tambah/ubah/hapus termin = addendum (Fase 4).
- **State machine:** `menunggu` -> `bukti_lengkap` (>=1 foto sebelum dan >=1 sesudah) -> `selesai` (konfirmasi pemilik DAN tukang). Hapus foto yang membuat satu jenis kosong mengembalikan ke `menunggu`. Konfirmasi pertama mengunci foto (tambah/hapus ditolak). Konfirmasi final, tidak ada "tolak" untuk termin.
- **Foto (I6):** disk `local` (`storage/app/private`), tidak pernah lewat `/storage`. Validasi isi file (`mimetypes` via finfo) lalu dibaca ulang GD; semua keluaran JPEG baru (kualitas 80, sisi terpanjang 1600 px), sehingga EXIF/GPS tidak ikut. Orientasi EXIF (3/6/8) diterapkan ke piksel sebelum dibuang. Waktu = `created_at` server. Disimpan sha256 file hasil. Maks 10 foto per jenis, 10 MB per unggahan, 30 MP.
- **HEIC ditolak** dengan pesan jelas (GD tidak bisa membaca). `<input accept>` memakai daftar tipe eksplisit tanpa `capture`: di ponsel, pemilih file menawarkan kamera dan galeri, dan iOS mengirim JPEG.
- **Akses foto:** pemilik lewat `AgreementPolicy::view` (bukan pemilik = 404). Pihak lawan lewat `/k/{token}/foto/{photo}`: URL bertanda tangan + token masih berlaku + foto harus milik termin target token. Limiter 60/menit terpisah dari 10/menit halaman konfirmasi.
- **Penutupan:** pembuat konfirmasi di aplikasi, pihak lawan lewat tautan `konfirmasi_termin` (service Fase 2, route `/k/{token}` yang sama; halaman dipilih dari `purpose` token).
- **Timeline React tanpa build:** npm masih tidak terjangkau, jadi React 18.3.1 UMD di-vendor ke `public/vendor/react/` (bukan CDN) dan komponen ada di `public/js/timeline.js` memakai `createElement` (tanpa JSX). Blade merender daftar yang sama sebagai fallback; React menggantinya hanya bila fetch JSON berhasil. Pindahkan ke `resources/js` + Vite setelah npm jalan.
- **Status `selesai`:** kesepakatan menjadi `selesai` otomatis saat konfirmasi kedua termin terakhir tercatat (`TerminFlow::confirm`). Setelah itu addendum dan foto ditutup (kebijakan hanya mengizinkan `aktif`/`perlu_diubah`).
- **Belum dikerjakan:** pencabutan tautan termin lama.

## 2026-11-09 Fase 4 (addendum, riwayat versi, kode versi)
- **docs/SPEC.md dan docs/ARCHITECTURE.md tetap tidak ada.** Acuan: daftar tugas Fase 4 dari pesan sesi ini.
- **Addendum = `AgreementVersion` baru, `kind = 'addendum'`** (kolom baru, default `awal`; migrasi `2026_11_09_100000`). `kind` tidak masuk hash agar hash lama tetap valid; addendum selalu punya `reason` (wajib, ikut di-hash), jadi tidak bisa dipalsukan tanpa merusak hash. Versi lama tidak disentuh.
- **Status "menunggu" diturunkan, bukan disimpan:** `Agreement::pendingVersion()` = versi terakhir yang belum berlaku dan belum ditolak. `agreements.status` tetap `aktif` selama ada versi berlaku, jadi termin dan foto tetap jalan saat addendum menunggu. Bila belum pernah ada versi berlaku (v1 ditolak), addendum menaikkan status ke `menunggu_konfirmasi`.
- **Versi berlaku** tetap aturan I3 (nomor tertinggi dengan `setuju` pemilik DAN tukang). Satu pihak saja tidak cukup. Ringkasan menampilkan versi berlaku, bukan usulan.
- **Penolakan addendum** dicatat di `version_confirmations` (`tolak`); kesepakatan tetap `aktif` dengan versi lama. Alasan penolakan belum ada.
- **Usulan baru menggantikan usulan yang masih menunggu** (hanya versi terakhir yang bisa dikonfirmasi; tautan versi lama mati dengan 410). Mencegah buntu bila pihak lawan tidak merespons. Versi yang tergantikan tampil "Digantikan" di riwayat.
- **Hanya pembuat yang bisa mengusulkan** (pihak lawan tidak punya akun). Policy `amend`: status `aktif` atau `perlu_diubah`.
- **Termin hidup mengikuti versi berlaku baru** (`TerminFlow::apply`): termin dicocokkan per posisi; yang berubah diperbarui, yang baru dibuat, yang dihapus dibuang. Perubahan ditolak (saat usul dan saat berlaku) bila menyentuh termin yang sudah ada konfirmasi/selesai, atau menghapus termin yang sudah punya foto. Foto tidak pernah dihapus otomatis. Bila keadaan berubah antara usul dan persetujuan, konfirmasi pihak lawan gagal (halaman "tidak berlaku", `used_at` dibatalkan) dan pembuat perlu mengusulkan ulang.
- **Perbandingan:** riwayat membandingkan dengan versi sebelumnya dalam rantai; halaman pihak lawan dan ringkasan pembuat membandingkan dengan versi berlaku. Posisi termin dibandingkan per nomor urut. Bahasa UI: "Perubahan kesepakatan".
- **Kode versi** = 8 karakter pertama `content_hash`, huruf besar. Penanda integritas internal, bukan tanda tangan elektronik.
- **Refactor kecil:** parsing/validasi baris termin dipindah dari `WizardController` ke `App\Support\InstallmentRows` agar dipakai wizard dan addendum.

## 2026-11-09 Fase 5 (pengingat, driver notifikasi) dan F6 (draf)
- **docs/SPEC.md dan docs/ARCHITECTURE.md tetap tidak ada.** "Bagian 6" dan I7 tidak bisa dibaca; aturan pengingat di bawah adalah tafsiran saya dari tugas Fase 5. Cocokkan dengan spec asli.
- **Perlu diingatkan** = termin belum `selesai` pada kesepakatan `aktif` dengan jatuh tempo <= hari ini + `JANJI_REMINDER_DAYS` (default 2). Jenis `jelang` bila belum lewat, `lewat` bila sudah. Termin yang awalnya `jelang` mendapat baris `lewat` baru kemudian (maks 2 pengingat per termin).
- **Idempoten:** `reminder_log` unik `(termin_id, kind)` dan `insertOrIgnore`. Command `janji:pengingat` (terjadwal 07:00) hanya menandai; tidak mengirim.
- **Pengiriman = tombol di dashboard** ("Perlu diingatkan"). Tombol mengisi `sent_at` sekali, lalu membuka `wa_link`. Panel menampilkan hanya yang `sent_at` kosong dan termin belum selesai. Mengirim ke pihak lawan diputuskan pembuat, bukan sistem.
- **sms / whatsapp_api:** `QueuedNotifier` hanya mengantre `SendNotification`. Job no-op + log warning bila `janji.channels.*.enabled` false (default); bila true, sengaja melempar karena klien belum ada. Tidak ada HTTP ke layanan nyata.
- **F6 dikerjakan karena Fase 1-4 lulus** (89 tes sebelum F5). `AgreementDrafter`: `manual` (default) dan `llm` (aktif hanya bila `JANJI_DRAFTER=llm` dan `ANTHROPIC_API_KEY` ada; selain itu jatuh ke manual). Keluaran LLM diperlakukan tak tepercaya: harus JSON lolos validasi (judul, lingkup, termin <= 6; harga/tanggal boleh null, tidak dikarang). Gagal = pesan galat, tidak ada draf.
- **Draf tidak pernah tersimpan otomatis:** `POST /kesepakatan/draf` hanya menampilkan formulir tinjauan. Penyimpanan butuh `POST /kesepakatan/draf/simpan` yang memakai validasi wizard (`InstallmentRows`, jumlah termin = total) dan membuat kesepakatan berstatus `draft`.
- **Transcriber:** interface + `FakeTranscriber` saja, belum dihubungkan ke UI.
- Model LLM default `claude-sonnet-5-5` (`JANJI_LLM_MODEL`). Belum diuji ke API sungguhan.
