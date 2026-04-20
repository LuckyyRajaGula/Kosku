@extends('layouts.app')

@section('title', 'Manajemen Pengguna - KosKu')

@section('content')
@if (session('success'))
    <div class="alert success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert error">{{ $errors->first() }}</div>
@endif

<section class="page-header">
    <div>
        <h2>Manajemen Pengguna</h2>
        <p>Kelola akun pengelola kost (khusus pemilik).</p>
    </div>
</section>

<section class="filter-panel">
    <h3 class="section-title" style="margin-top:0;">Tambah Akun Pengelola</h3>
    <form method="POST" action="{{ route('pengguna.store') }}" class="filter-grid">
        @csrf
        <div>
            <label for="nama">Nama</label>
            <input id="nama" type="text" name="nama" value="{{ old('nama') }}" required>
        </div>
        <div>
            <label for="username">Username</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
        </div>
        <div>
            <label for="no_telpon">No Telpon</label>
            <input id="no_telpon" type="text" name="no_telpon" value="{{ old('no_telpon') }}">
        </div>
        <div class="actions">
            <button type="submit" class="primary-btn">Simpan Akun</button>
        </div>
    </form>
</section>

<section class="room-group">
    <header>
        <div>
            <h3>Daftar Pengelola</h3>
            <p>Total {{ $pengelola->count() }} akun pengelola</p>
        </div>
    </header>

    @if ($pengelola->isEmpty())
        <div class="empty-state" style="margin: 12px;">Belum ada akun pengelola.</div>
    @else
        <div class="room-grid">
            @foreach ($pengelola as $manager)
                <article class="room-card">
                    <div class="room-head">
                        <strong>{{ $manager->nama }}</strong>
                        <span class="badge {{ strtolower($manager->status_akun) === 'aktif' ? 'kosong' : 'maintenance' }}">{{ $manager->status_akun }}</span>
                    </div>
                    <p class="room-type">{{ $manager->username }}</p>
                    <p class="room-price">{{ $manager->email }}</p>

                    <div class="room-meta">
                        <small>No Telpon</small>
                        <p>{{ $manager->no_telpon ?: '-' }}</p>
                    </div>

                    <form method="POST" action="{{ route('pengguna.update', $manager->id_user) }}" class="room-actions" style="margin-top:12px; flex-direction:column;">
                        @csrf
                        @method('PUT')
                        <input type="text" name="nama" value="{{ $manager->nama }}" required>
                        <input type="text" name="username" value="{{ $manager->username }}" required>
                        <input type="email" name="email" value="{{ $manager->email }}" required>
                        <input type="text" name="no_telpon" value="{{ $manager->no_telpon }}" placeholder="No telpon">
                        <input type="password" name="password" placeholder="Password baru (opsional)">
                        <button type="submit" class="primary-btn" style="width:100%;">Update Akun</button>
                    </form>

                    <form method="POST" action="{{ route('pengguna.status', $manager->id_user) }}" style="margin-top:8px;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status_akun" value="{{ $manager->status_akun === 'Aktif' ? 'Nonaktif' : 'Aktif' }}">
                        <button type="submit" class="ghost-btn" style="width:100%;">
                            {{ $manager->status_akun === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }} Akun
                        </button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
