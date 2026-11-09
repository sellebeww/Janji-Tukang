<?php

use App\Http\Controllers\AddendumController;
use App\Http\Controllers\AgreementController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\KonfirmasiController;
use App\Http\Controllers\PengingatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TerminController;
use App\Http\Controllers\WizardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::view('/privasi', 'privasi')->name('privasi');

Route::redirect('/dashboard', '/kesepakatan')->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/kesepakatan', [AgreementController::class, 'index'])->name('agreements.index');
    Route::get('/kesepakatan/baru', [WizardController::class, 'lingkup'])->name('wizard.baru');
    Route::post('/kesepakatan', [WizardController::class, 'saveLingkup'])->name('wizard.simpan');
    Route::get('/kesepakatan/draf', [DraftController::class, 'form'])->name('draft.form');
    Route::post('/kesepakatan/draf', [DraftController::class, 'generate'])->name('draft.generate');
    Route::post('/kesepakatan/draf/simpan', [DraftController::class, 'store'])->name('draft.simpan');
    Route::post('/pengingat/{log}/kirim', [PengingatController::class, 'send'])->name('pengingat.kirim');
    Route::get('/kesepakatan/{agreement}', [AgreementController::class, 'show'])->name('agreements.show');
    Route::get('/kesepakatan/{agreement}/lingkup', [WizardController::class, 'lingkup'])->name('wizard.lingkup');
    Route::put('/kesepakatan/{agreement}/lingkup', [WizardController::class, 'saveLingkup']);
    Route::get('/kesepakatan/{agreement}/harga', [WizardController::class, 'harga'])->name('wizard.harga');
    Route::put('/kesepakatan/{agreement}/harga', [WizardController::class, 'saveHarga']);
    Route::get('/kesepakatan/{agreement}/termin', [WizardController::class, 'termin'])->name('wizard.termin');
    Route::put('/kesepakatan/{agreement}/termin', [WizardController::class, 'saveTermin']);
    Route::post('/kesepakatan/{agreement}/ajukan', [AgreementController::class, 'submit'])->name('agreements.submit');
    Route::post('/kesepakatan/{agreement}/tautan', [AgreementController::class, 'link'])->name('agreements.link');
    Route::get('/kesepakatan/{agreement}/ubah', [AddendumController::class, 'create'])->name('addendum.create');
    Route::post('/kesepakatan/{agreement}/ubah', [AddendumController::class, 'store'])->name('addendum.store');
    Route::get('/kesepakatan/{agreement}/riwayat', [AddendumController::class, 'history'])->name('agreements.riwayat');
    Route::get('/kesepakatan/{agreement}/timeline', [TerminController::class, 'timeline'])->name('agreements.timeline');

    Route::get('/termin/{termin}', [TerminController::class, 'show'])->name('termin.show');
    Route::post('/termin/{termin}/foto', [TerminController::class, 'storePhoto'])->name('termin.foto.simpan');
    Route::post('/termin/{termin}/konfirmasi', [TerminController::class, 'confirm'])->name('termin.konfirmasi');
    Route::post('/termin/{termin}/tautan', [TerminController::class, 'link'])->name('termin.tautan');
    Route::get('/foto/{photo}', [TerminController::class, 'photo'])->name('foto.tampil');
    Route::delete('/foto/{photo}', [TerminController::class, 'destroyPhoto'])->name('foto.hapus');
});

// Pihak lawan, tanpa akun. throttle di depan signed supaya tebakan tanda tangan ikut dihitung.
Route::middleware(['throttle:konfirmasi', 'signed'])->group(function () {
    Route::get('/k/{token}', [KonfirmasiController::class, 'show'])->name('konfirmasi.show');
    Route::post('/k/{token}', [KonfirmasiController::class, 'store'])->name('konfirmasi.store');
});

// Foto untuk pihak lawan: tanda tangan + token masih berlaku. Limiter terpisah karena satu halaman memuat banyak foto.
Route::middleware(['throttle:60,1', 'signed'])->get('/k/{token}/foto/{photo}', [KonfirmasiController::class, 'foto'])->name('konfirmasi.foto');

require __DIR__.'/auth.php';
