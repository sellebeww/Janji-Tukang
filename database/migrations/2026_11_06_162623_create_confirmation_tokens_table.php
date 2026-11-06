<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confirmation_tokens', function (Blueprint $table) {
            $table->id();
            $table->char('token_hash', 64)->unique(); // token mentah tidak pernah disimpan
            $table->string('purpose', 30); // satu tujuan
            $table->unsignedBigInteger('target_id'); // satu target (id sesuai purpose)
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmation_tokens');
    }
};
