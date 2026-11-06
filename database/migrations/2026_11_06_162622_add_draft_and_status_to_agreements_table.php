<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            // draft | menunggu_konfirmasi | aktif | perlu_diubah
            $table->string('status', 30)->default('draft')->after('counterparty_phone');
            // Isi draf. Setelah diajukan, kebenaran ada di agreement_versions (append-only).
            $table->text('scope')->nullable();
            $table->unsignedBigInteger('total_amount')->nullable();
            $table->json('installments')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn(['status', 'scope', 'total_amount', 'installments']);
        });
    }
};
