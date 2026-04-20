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
        Schema::create('user', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('nama', 100);
            $table->string('username', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->string('no_telpon', 15)->nullable();
            $table->string('role', 20);
        });

        Schema::create('kamar', function (Blueprint $table) {
            $table->increments('id_kamar');
            $table->string('no_kamar', 10)->unique();
            $table->string('tipe_kamar', 50)->nullable();
            $table->decimal('harga', 10, 2);
            $table->string('status_ketersediaan', 20)->default('Kosong');
            $table->text('fasilitias')->nullable();
            $table->string('luas_kamar', 20)->nullable();
        });

        Schema::create('penyewa', function (Blueprint $table) {
            $table->increments('id_penyewa');
            $table->unsignedInteger('id_user')->unique()->nullable();
            $table->unsignedInteger('id_kamar')->nullable();
            $table->string('nama', 100);
            $table->string('ktp', 20)->unique()->nullable();
            $table->string('kontrak', 50)->nullable();
            $table->string('dokumen_kontrak', 255)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->date('tanggal_selesai')->nullable();

            $table->foreign('id_user')->references('id_user')->on('user')->onDelete('cascade');
            $table->foreign('id_kamar')->references('id_kamar')->on('kamar')->onDelete('set null');
        });

        Schema::create('laporan', function (Blueprint $table) {
            $table->increments('id_laporan');
            $table->unsignedInteger('id_user')->nullable();
            $table->string('jenis_laporan', 50)->nullable();
            $table->string('periode', 50)->nullable();
            $table->text('isi_laporan')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->date('tanggal_buat')->nullable();

            $table->foreign('id_user')->references('id_user')->on('user')->onDelete('set null');
        });

        Schema::create('komplain', function (Blueprint $table) {
            $table->increments('id_komplain');
            $table->unsignedInteger('id_penyewa')->nullable();
            $table->string('jenis_komplain', 100)->nullable();
            $table->text('deskripsi')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('status_penanganan', 50)->default('Diajukan');
            $table->text('respon')->nullable();
            $table->date('tanggal_selesai')->nullable();

            $table->foreign('id_penyewa')->references('id_penyewa')->on('penyewa')->onDelete('cascade');
        });

        Schema::create('tagihan_pembayaran', function (Blueprint $table) {
            $table->increments('id_tagihan');
            $table->unsignedInteger('id_penyewa')->nullable();
            $table->string('periode', 50)->nullable();
            $table->decimal('nominal', 10, 2);
            $table->date('tanggal_jatuh_tempo');
            $table->date('tanggal_bayar')->nullable();
            $table->string('metode_pembayaran', 50)->nullable();
            $table->string('bukti_bayar', 255)->nullable();
            $table->string('status', 20)->default('Belum Bayar');

            $table->foreign('id_penyewa')->references('id_penyewa')->on('penyewa')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_pembayaran');
        Schema::dropIfExists('komplain');
        Schema::dropIfExists('laporan');
        Schema::dropIfExists('penyewa');
        Schema::dropIfExists('kamar');
        Schema::dropIfExists('user');
    }
};
