# Janji Tukang

Alat catatan kesepakatan kerja renovasi kecil antara pemilik rumah dan tukang/mandor.
"Dari obrolan di teras jadi kesepakatan yang tercatat."

Ini catatan bersama dua pihak, bukan nasihat hukum. Aplikasi tidak memegang uang.

## Menjalankan
```
composer install
cp .env.example .env && php artisan key:generate
# buat database MySQL `janji_tukang` dan `janji_tukang_test`
php artisan migrate
npm install && npm run dev
php artisan test
```

Milik Vassel Goleyu. Lihat LICENSE dan docs/DECISIONS.md.
