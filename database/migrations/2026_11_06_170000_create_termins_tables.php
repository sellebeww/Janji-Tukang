<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Termin hidup: salinan rencana versi berlaku. Judul/nominal/jatuh tempo tidak diubah di sini (perubahan = addendum, Fase 4).
        Schema::create('termins', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('agreement_id')->constrained()->restrictOnDelete();
            $table->foreignId('agreement_version_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('title');
            $table->unsignedBigInteger('amount');
            $table->date('due_date');
            $table->string('status', 20)->default('menunggu'); // menunggu | bukti_lengkap | selesai
            $table->timestamps();

            $table->unique(['agreement_id', 'position']);
        });

        Schema::create('termin_photos', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('termin_id')->constrained()->restrictOnDelete();
            $table->string('kind', 10); // sebelum | sesudah
            $table->string('path');
            $table->char('sha256', 64);
            $table->unsignedInteger('size');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at'); // waktu server; tidak pernah dari klien/EXIF
        });

        Schema::create('termin_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('termin_id')->constrained()->restrictOnDelete();
            $table->string('party', 10); // pemilik | tukang
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at');

            $table->unique(['termin_id', 'party']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termin_confirmations');
        Schema::dropIfExists('termin_photos');
        Schema::dropIfExists('termins');
    }
};
