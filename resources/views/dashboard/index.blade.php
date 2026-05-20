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
            <h3>{{ $totalMaintenance + $komplainAktif }}</h3>
            <small>{{ $totalMaintenance }} maintenance, {{ $komplainAktif }} komplain</small>
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
                <small>{{ $komplainAktif }} komplain menunggu penanganan</small>
            </div>
        </a>
        <a href="{{ route('pembayaran') }}" class="quick-action">
            <i class="bi bi-credit-card"></i>
            <div>
                <strong>Pembayaran</strong>
                <small>{{ $pembayaranPending }} tagihan belum dibayar</small>
            </div>
        </a>
        <a href="{{ route('laporan') }}" class="quick-action">
            <i class="bi bi-graph-up-arrow"></i>
            <div>
                <strong>Laporan Keuangan</strong>
                <small>Analisis pendapatan</small>
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
        <article class="stat-card"><p>Pembayaran Pending</p><h3>{{ $pembayaranPending }}</h3><small>Perlu tindak lanjut</small></article>
        <article class="stat-card"><p>Komplain Aktif</p><h3>{{ $komplainAktif }}</h3><small>Perlu ditangani</small></article>
    </section>

    <h3 class="section-title">Aksi Cepat</h3>
    <section class="quick-grid four">
        <a href="{{ route('penyewa') }}" class="quick-action"><i class="bi bi-people"></i><div><strong>Tambah Penyewa</strong></div></a>
        <a href="{{ route('pembayaran') }}" class="quick-action"><i class="bi bi-credit-card"></i><div><strong>Catat Pembayaran</strong></div></a>
        <a href="{{ route('kamar') }}" class="quick-action"><i class="bi bi-door-open"></i><div><strong>Update Kamar</strong></div></a>
        <a href="{{ route('komplain') }}" class="quick-action"><i class="bi bi-chat-left-text"></i><div><strong>Tangani Komplain</strong></div></a>
    </section>
@else
    {{-- PENYEWA DASHBOARD --}}
    <section class="hero hero-tenant">
        <p>Halo,</p>
        <h2>{{ $user['nama'] }}</h2>
        @if ($tenantData)
            <small>Kamar {{ $tenantData->no_kamar ?: '-' }} · KosKu</small>
        @else
            <small>Selamat datang di KosKu</small>
        @endif
    </section>

    @if ($tenantData)
        <section class="tenant-card">
            <header>
                <strong>Detail Kamar</strong>
                <span>● Aktif</span>
            </header>
            <div class="tenant-grid">
                <div><small>Nomor Kamar</small><p>{{ $tenantData->no_kamar ?: '-' }}</p></div>
                <div><small>Tipe Kamar</small><p>{{ $tenantData->tipe_kamar ?: '-' }}</p></div>
                <div><small>Kontrak Mulai</small><p>{{ $tenantData->tanggal_masuk ? \Carbon\Carbon::parse($tenantData->tanggal_masuk)->translatedFormat('d M Y') : '-' }}</p></div>
                <div><small>Kontrak Berakhir</small><p>{{ $tenantData->tanggal_keluar ? \Carbon\Carbon::parse($tenantData->tanggal_keluar)->translatedFormat('d M Y') : '-' }}</p></div>
            </div>
        </section>

        @if ($tenantBill)
            <section class="tenant-card bill">
                <header>
                    <strong>Tagihan Terbaru</strong>
                    <span class="{{ $tenantBill->status === 'Lunas' ? '' : 'warn' }}">{{ $tenantBill->status }}</span>
                </header>
                <div class="bill-row">
                    <div><small>Periode</small><p>{{ $tenantBill->periode }}</p></div>
                    <div><small>Jatuh Tempo</small><p>{{ $tenantBill->tanggal_jatuh_tempo }}</p></div>
                    <div><small>Nominal</small><p>Rp {{ number_format($tenantBill->nominal, 0, ',', '.') }}</p></div>
                </div>
                @if ($tenantBill->status !== 'Lunas')
                    <div style="margin-top:12px;padding:10px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;">
                        <p style="font-size:13px;color:#92400e;">⚠️ Segera lakukan pembayaran untuk menghindari denda.</p>
                    </div>
                @endif
            </section>
        @else
            <section class="tenant-card">
                <header>
                    <strong>Tagihan</strong>
                    <span>-</span>
                </header>
                <p style="color:#64748b;padding:8px 0;">Belum ada tagihan yang tercatat.</p>
            </section>
        @endif

        <section class="quick-grid">
            <a href="{{ route('komplain') }}" class="quick-action">
                <i class="bi bi-chat-left-text"></i>
                <div>
                    <strong>Ajukan Komplain</strong>
                    <small>{{ $tenantKomplainCount > 0 ? $tenantKomplainCount . ' komplain aktif' : 'Sampaikan keluhan' }}</small>
                </div>
            </a>
            <a href="{{ route('pembayaran') }}" class="quick-action">
                <i class="bi bi-credit-card"></i>
                <div>
                    <strong>Riwayat Pembayaran</strong>
                    <small>Lihat semua tagihan</small>
                </div>
            </a>
        </section>
    @else
        <section class="tenant-card">
            <header>
                <strong>Info</strong>
                <span class="warn">Belum ada kamar</span>
            </header>
            <p style="color:#64748b;padding:8px 0;">Data penyewa belum ditemukan atau kontrak sudah berakhir. Hubungi pengelola kost.</p>
        </section>
    @endif
@endif
@endsection
