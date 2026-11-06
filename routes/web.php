<?php

use App\Http\Controllers\AgreementController;
use App\Http\Controllers\KonfirmasiController;
use App\Http\Controllers\ProfileController;
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
    Route::get('/kesepakatan/{agreement}', [AgreementController::class, 'show'])->name('agreements.show');
    Route::get('/kesepakatan/{agreement}/lingkup', [WizardController::class, 'lingkup'])->name('wizard.lingkup');
    Route::put('/kesepakatan/{agreement}/lingkup', [WizardController::class, 'saveLingkup']);
    Route::get('/kesepakatan/{agreement}/harga', [WizardController::class, 'harga'])->name('wizard.harga');
    Route::put('/kesepakatan/{agreement}/harga', [WizardController::class, 'saveHarga']);
    Route::get('/kesepakatan/{agreement}/termin', [WizardController::class, 'termin'])->name('wizard.termin');
    Route::put('/kesepakatan/{agreement}/termin', [WizardController::class, 'saveTermin']);
    Route::post('/kesepakatan/{agreement}/ajukan', [AgreementController::class, 'submit'])->name('agreements.submit');
    Route::post('/kesepakatan/{agreement}/tautan', [AgreementController::class, 'link'])->name('agreements.link');
});

// Pihak lawan, tanpa akun. throttle di depan signed supaya tebakan tanda tangan ikut dihitung.
Route::middleware(['throttle:konfirmasi', 'signed'])->group(function () {
    Route::get('/k/{token}', [KonfirmasiController::class, 'show'])->name('konfirmasi.show');
    Route::post('/k/{token}', [KonfirmasiController::class, 'store'])->name('konfirmasi.store');
});

require __DIR__.'/auth.php';
