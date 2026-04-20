<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->string('status_akun', 20)->default('Aktif')->after('role');
            $table->index(['role', 'status_akun']);
        });

        Schema::table('kamar', function (Blueprint $table) {
            $table->index('status_ketersediaan');
        });

        Schema::table('penyewa', function (Blueprint $table) {
            $table->index(['id_kamar', 'tanggal_masuk', 'tanggal_keluar'], 'penyewa_status_kamar_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penyewa', function (Blueprint $table) {
            $table->dropIndex('penyewa_status_kamar_index');
        });

        Schema::table('kamar', function (Blueprint $table) {
            $table->dropIndex(['status_ketersediaan']);
        });

        Schema::table('user', function (Blueprint $table) {
            $table->dropIndex(['role', 'status_akun']);
            $table->dropColumn('status_akun');
        });
    }
};
