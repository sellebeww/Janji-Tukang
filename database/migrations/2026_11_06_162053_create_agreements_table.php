<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique(); // dipakai di URL publik
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('creator_role', 10); // pemilik | tukang
            $table->string('title', 150);
            $table->string('counterparty_name', 100);
            $table->string('counterparty_phone', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
