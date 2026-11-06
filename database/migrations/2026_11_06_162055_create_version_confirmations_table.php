<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('version_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_version_id')->constrained()->restrictOnDelete();
            $table->string('party', 10); // pemilik | tukang
            $table->string('decision', 10); // setuju | tolak
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at');

            $table->unique(['agreement_version_id', 'party']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('version_confirmations');
    }
};
