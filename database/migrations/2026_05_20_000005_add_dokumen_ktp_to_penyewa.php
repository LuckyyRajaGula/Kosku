<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penyewa', function (Blueprint $table) {
            $table->string('dokumen_ktp', 255)->nullable()->after('ktp');
        });
    }

    public function down(): void
    {
        Schema::table('penyewa', function (Blueprint $table) {
            $table->dropColumn('dokumen_ktp');
        });
    }
};
