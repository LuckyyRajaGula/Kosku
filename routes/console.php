<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pembangkitan tagihan bulanan otomatis — jalan setiap tanggal 1 pukul 00:00
Schedule::command('tagihan:generate')->monthlyOn(1, '00:00');

// Pengingat tagihan H-3 & H-1 — jalan setiap hari pukul 08:00
Schedule::command('tagihan:reminder')->dailyAt('08:00');
