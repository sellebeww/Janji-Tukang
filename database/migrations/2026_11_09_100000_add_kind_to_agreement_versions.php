<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreement_versions', function (Blueprint $table) {
            $table->string('kind', 10)->default('awal')->after('number'); // awal | addendum
        });
    }

    public function down(): void
    {
        Schema::table('agreement_versions', fn (Blueprint $table) => $table->dropColumn('kind'));
    }
};
