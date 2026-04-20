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
    Route::get('/kamar', [DashboardController::class, 'kamar'])->name('kamar');
    Route::post('/kamar', [DashboardController::class, 'storeKamar'])->name('kamar.store');
    Route::put('/kamar/{idKamar}', [DashboardController::class, 'updateKamar'])->name('kamar.update');
    Route::delete('/kamar/{idKamar}', [DashboardController::class, 'deleteKamar'])->name('kamar.delete');

    Route::get('/manajemen-pengguna', [DashboardController::class, 'pengguna'])->name('pengguna');
    Route::post('/manajemen-pengguna', [DashboardController::class, 'storePengelola'])->name('pengguna.store');
    Route::put('/manajemen-pengguna/{idUser}', [DashboardController::class, 'updatePengelola'])->name('pengguna.update');
    Route::patch('/manajemen-pengguna/{idUser}/status', [DashboardController::class, 'updateStatusPengelola'])->name('pengguna.status');

    Route::get('/penyewa', [DashboardController::class, 'penyewa'])->name('penyewa');
    Route::post('/penyewa', [DashboardController::class, 'storePenyewa'])->name('penyewa.store');
    Route::get('/pembayaran', [DashboardController::class, 'placeholder'])->defaults('module', 'pembayaran')->name('pembayaran');
    Route::get('/komplain', [DashboardController::class, 'placeholder'])->defaults('module', 'komplain')->name('komplain');
    Route::get('/laporan', [DashboardController::class, 'placeholder'])->defaults('module', 'laporan')->name('laporan');
});
