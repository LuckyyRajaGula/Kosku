@extends('layouts.app')

@section('title', 'Manajemen Penyewa - KosKu')

@section('content')
@if (session('success'))
    <div class="alert success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert error">{{ $errors->first() }}</div>
@endif

<section class="page-header">
    <div>
        <h2>Manajemen Penyewa</h2>
        <p>Check-in penyewa baru dan pantau data kontrak.</p>
    </div>
</section>

<section class="filter-panel">
    <h3 class="section-title" style="margin-top:0;">Check-in Penyewa Baru</h3>
    <form method="POST" action="{{ route('penyewa.store') }}" enctype="multipart/form-data" class="filter-grid">
        @csrf
        <div>
            <label for="nama">Nama Penyewa</label>
            <input id="nama" type="text" name="nama" value="{{ old('nama') }}" required>
        </div>
        <div>
            <label for="ktp">No KTP</label>
            <input id="ktp" type="text" name="ktp" value="{{ old('ktp') }}">
        </div>
        <div>
            <label for="kontrak">Jenis Kontrak</label>
            <input id="kontrak" type="text" name="kontrak" value="{{ old('kontrak') }}" placeholder="Bulanan/Tahunan">
        </div>
        <div>
            <label for="id_kamar">Pilih Kamar Kosong</label>
            <select id="id_kamar" name="id_kamar" required>
                <option value="">-- Pilih Kamar --</option>
                @foreach ($availableRooms as $room)
                    <option value="{{ $room->id_kamar }}" {{ old('id_kamar') == $room->id_kamar ? 'selected' : '' }}>
                        {{ $room->no_kamar }} ({{ $room->tipe_kamar ?: 'Tipe Umum' }}) - Rp {{ number_format($room->harga, 0, ',', '.') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tanggal_masuk">Tanggal Masuk</label>
            <input id="tanggal_masuk" type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk') }}" required>
        </div>
        <div>
            <label for="tanggal_keluar">Tanggal Keluar</label>
            <input id="tanggal_keluar" type="date" name="tanggal_keluar" value="{{ old('tanggal_keluar') }}">
        </div>
        <div>
            <label for="dokumen_kontrak">Dokumen Kontrak</label>
            <input id="dokumen_kontrak" type="file" name="dokumen_kontrak" accept=".pdf,.jpg,.jpeg,.png">
        </div>

        <div>
            <label for="username">Username Login Penyewa</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required>
        </div>
        <div>
            <label for="email">Email Penyewa</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div>
            <label for="password">Password Awal</label>
            <input id="password" type="password" name="password" required>
        </div>
        <div>
            <label for="no_telpon">No Telpon</label>
            <input id="no_telpon" type="text" name="no_telpon" value="{{ old('no_telpon') }}">
        </div>

        <div class="actions">
            <button type="submit" class="primary-btn">Simpan Check-in</button>
        </div>
    </form>
</section>

<section class="room-group">
    <header>
        <div>
            <h3>Data Penyewa</h3>
            <p>Total {{ $tenants->count() }} penyewa tercatat</p>
        </div>
    </header>

    @if ($tenants->isEmpty())
        <div class="empty-state" style="margin:12px;">Belum ada data penyewa.</div>
    @else
        <div class="room-grid">
            @foreach ($tenants as $tenant)
                <article class="room-card">
                    <div class="room-head">
                        <strong>{{ $tenant->nama }}</strong>
                        <span class="badge {{ strtolower($tenant->status_ketersediaan ?? 'maintenance') }}">{{ $tenant->status_ketersediaan ?? 'Tanpa Kamar' }}</span>
                    </div>
                    <p class="room-type">KTP: {{ $tenant->ktp ?: '-' }}</p>
                    <p class="room-price">Kamar: {{ $tenant->no_kamar ?: '-' }}</p>

                    <div class="room-meta">
                        <small>Kontrak</small>
                        <p>{{ $tenant->kontrak ?: '-' }}</p>
                    </div>
                    <div class="room-meta">
                        <small>Periode</small>
                        <p>{{ $tenant->tanggal_masuk ?: '-' }} s/d {{ $tenant->tanggal_keluar ?: '-' }}</p>
                    </div>

                    @if ($tenant->dokumen_kontrak)
                        <div class="room-actions">
                            <a class="ghost-btn" style="width:100%;" target="_blank" href="{{ asset('storage/' . $tenant->dokumen_kontrak) }}">Lihat Dokumen Kontrak</a>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
