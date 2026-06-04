<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

Route::middleware('kosku.auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    // Kamar
    Route::get('/kamar', [DashboardController::class, 'kamar'])->name('kamar');
    Route::post('/kamar', [DashboardController::class, 'storeKamar'])->name('kamar.store');
    Route::put('/kamar/{idKamar}', [DashboardController::class, 'updateKamar'])->name('kamar.update');
    Route::delete('/kamar/{idKamar}', [DashboardController::class, 'deleteKamar'])->name('kamar.delete');

    // Manajemen Pengguna (Pengelola)
    Route::get('/manajemen-pengguna', [DashboardController::class, 'pengguna'])->name('pengguna');
    Route::post('/manajemen-pengguna', [DashboardController::class, 'storePengelola'])->name('pengguna.store');
    Route::put('/manajemen-pengguna/{idUser}', [DashboardController::class, 'updatePengelola'])->name('pengguna.update');
    Route::patch('/manajemen-pengguna/{idUser}/status', [DashboardController::class, 'updateStatusPengelola'])->name('pengguna.status');

    // Penyewa
    Route::get('/penyewa', [DashboardController::class, 'penyewa'])->name('penyewa');
    Route::post('/penyewa', [DashboardController::class, 'storePenyewa'])->name('penyewa.store');
    Route::put('/penyewa/{idPenyewa}', [DashboardController::class, 'updatePenyewa'])->name('penyewa.update');
    Route::patch('/penyewa/{idPenyewa}/checkout', [DashboardController::class, 'checkoutPenyewa'])->name('penyewa.checkout');

    // Pembayaran
    Route::get('/pembayaran', [DashboardController::class, 'pembayaran'])->name('pembayaran');
    Route::post('/pembayaran', [DashboardController::class, 'storeTagihan'])->name('pembayaran.store');
    Route::put('/pembayaran/{idTagihan}', [DashboardController::class, 'updateTagihan'])->name('pembayaran.update');
    Route::delete('/pembayaran/{idTagihan}', [DashboardController::class, 'deleteTagihan'])->name('pembayaran.delete');
    Route::post('/pembayaran/{idTagihan}/upload-bukti', [DashboardController::class, 'uploadBuktiBayar'])->name('pembayaran.upload-bukti');
    Route::patch('/pembayaran/{idTagihan}/tandai-lunas', [DashboardController::class, 'tandaiLunas'])->name('pembayaran.tandai-lunas');

    // Komplain
    Route::get('/komplain', [DashboardController::class, 'komplain'])->name('komplain');
    Route::post('/komplain', [DashboardController::class, 'storeKomplain'])->name('komplain.store');
    Route::put('/komplain/{idKomplain}', [DashboardController::class, 'updateKomplain'])->name('komplain.update');
    Route::delete('/komplain/{idKomplain}', [DashboardController::class, 'deleteKomplain'])->name('komplain.delete');

    // Laporan
    Route::get('/laporan', [DashboardController::class, 'laporan'])->name('laporan');
    Route::get('/laporan/export', [DashboardController::class, 'exportLaporan'])->name('laporan.export');
    Route::get('/laporan/export-pdf', [DashboardController::class, 'exportLaporanPdf'])->name('laporan.export-pdf');
});
