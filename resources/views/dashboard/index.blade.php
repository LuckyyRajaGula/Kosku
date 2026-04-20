@extends('layouts.app')

@section('title', 'Dashboard - KosKu')

@section('content')
@php
    $role = $user['role'] ?? 'penyewa';
@endphp

@if ($role === 'pemilik')
    <section class="hero hero-owner">
        <p>Selamat datang kembali,</p>
        <h2>{{ $user['nama'] }}</h2>
        <small>Berikut ringkasan properti kost Anda hari ini.</small>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <p>Total Kamar</p>
            <h3>{{ $totalKamar }}</h3>
            <small>Keseluruhan unit kamar</small>
        </article>
        <article class="stat-card">
            <p>Tingkat Hunian</p>
            <h3>{{ $tingkatHunian }}%</h3>
            <small>{{ $totalTerisi }} kamar terisi</small>
        </article>
        <article class="stat-card">
            <p>Pendapatan/Bulan</p>
            <h3>Rp {{ number_format($pendapatanBulan, 0, ',', '.') }}</h3>
            <small>Estimasi kamar terisi</small>
        </article>
        <article class="stat-card">
            <p>Perlu Perhatian</p>
            <h3>{{ $totalMaintenance }}</h3>
            <small>Kamar maintenance</small>
        </article>
    </section>

    <h3 class="section-title">Ringkasan per Properti</h3>
    <section class="property-grid">
        @foreach ($propertyCards as $property)
            <article class="property-card">
                <header>
                    <p>{{ $property['nama'] }}</p>
                    <strong>{{ $property['hunian'] }}%</strong>
                </header>
                <div class="meter">
                    <span style="width: {{ $property['hunian'] }}%"></span>
                </div>
                <div class="property-stats">
                    <div><strong>{{ $property['terisi'] }}</strong><small>Terisi</small></div>
                    <div><strong>{{ $property['kosong'] }}</strong><small>Kosong</small></div>
                    <div><strong>{{ $property['maintenance'] }}</strong><small>Service</small></div>
                </div>
            </article>
        @endforeach
    </section>

    <h3 class="section-title">Aksi Cepat</h3>
    <section class="quick-grid">
        <a href="{{ route('kamar') }}" class="quick-action">
            <i class="bi bi-door-open"></i>
            <div>
                <strong>Manajemen Kamar</strong>
                <small>Lihat dan kelola seluruh kamar</small>
            </div>
        </a>
        <a href="{{ route('komplain') }}" class="quick-action">
            <i class="bi bi-chat-left-text"></i>
            <div>
                <strong>Komplain Aktif</strong>
                <small>Tinjau komplain terbaru penyewa</small>
            </div>
        </a>
    </section>
@elseif ($role === 'pengelola')
    <section class="hero hero-manager">
        <p>Pengelola aktif,</p>
        <h2>{{ $user['nama'] }}</h2>
        <small>Kelola operasional kost dengan efisien.</small>
    </section>

    <section class="stats-grid">
        <article class="stat-card"><p>Kamar Kosong</p><h3>{{ $totalKosong }}</h3><small>Siap disewakan</small></article>
        <article class="stat-card"><p>Penyewa Aktif</p><h3>{{ $totalTerisi }}</h3><small>Data terhubung kamar</small></article>
        <article class="stat-card"><p>Pembayaran Pending</p><h3>3</h3><small>Perlu tindak lanjut</small></article>
        <article class="stat-card"><p>Komplain Aktif</p><h3>4</h3><small>Perlu ditangani</small></article>
    </section>

    <h3 class="section-title">Aksi Cepat</h3>
    <section class="quick-grid four">
        <a href="{{ route('penyewa') }}" class="quick-action"><i class="bi bi-people"></i><div><strong>Tambah Penyewa</strong></div></a>
        <a href="{{ route('pembayaran') }}" class="quick-action"><i class="bi bi-credit-card"></i><div><strong>Catat Pembayaran</strong></div></a>
        <a href="{{ route('kamar') }}" class="quick-action"><i class="bi bi-door-open"></i><div><strong>Update Kamar</strong></div></a>
        <a href="{{ route('komplain') }}" class="quick-action"><i class="bi bi-chat-left-text"></i><div><strong>Tangani Komplain</strong></div></a>
    </section>
@else
    <section class="hero hero-tenant">
        <p>Halo,</p>
        <h2>{{ $user['nama'] }}</h2>
        <small>Kamar A101 · KosKu Dago</small>
    </section>

    <section class="tenant-card">
        <header>
            <strong>Detail Kamar</strong>
            <span>Aktif</span>
        </header>
        <div class="tenant-grid">
            <div><small>Properti</small><p>KosKu Dago</p></div>
            <div><small>Nomor Kamar</small><p>A101</p></div>
            <div><small>Kontrak Mulai</small><p>1 Jan 2026</p></div>
            <div><small>Kontrak Berakhir</small><p>1 Jan 2027</p></div>
        </div>
    </section>

    <section class="tenant-card bill">
        <header>
            <strong>Tagihan Bulan Ini</strong>
            <span class="warn">Belum Bayar</span>
        </header>
        <div class="bill-row">
            <div><small>Jatuh Tempo</small><p>20 April 2026</p></div>
            <div><small>Nominal</small><p>Rp 1.500.000</p></div>
        </div>
    </section>
@endif
@endsection
