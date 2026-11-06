<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_versions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('agreement_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('number');
            $table->text('scope');
            $table->unsignedBigInteger('total_amount'); // rupiah bulat (I1)
            $table->json('installments'); // rencana termin: title, amount, due_date
            $table->text('reason')->nullable(); // alasan perubahan (versi > 1)
            $table->char('previous_hash', 64)->nullable();
            $table->char('content_hash', 64);
            $table->timestamp('created_at');

            $table->unique(['agreement_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_versions');
    }
};
