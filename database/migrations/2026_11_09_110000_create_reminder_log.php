<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_log', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('termin_id')->constrained()->restrictOnDelete();
            $table->string('kind', 10); // jelang | lewat
            $table->timestamp('sent_at')->nullable(); // diisi saat pembuat menekan tombol kirim
            $table->timestamp('created_at');

            // Kunci idempotensi: satu pengingat per termin per jenis, berapa kali pun command jalan.
            $table->unique(['termin_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_log');
    }
};
