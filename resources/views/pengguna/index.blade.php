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
    <form method="POST" action="{{ route('pengguna.store') }}" class="form-grid">
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
        <div class="actions" style="grid-column: span 3;">
            <button type="submit" class="primary-btn" style="width: auto; min-width: 160px;">Simpan Akun</button>
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

                    <details class="edit-details" style="margin-top: 10px; width: 100%;">
                        <summary class="ghost-btn" style="width: 100%; min-height: 36px; font-size: 13px; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 6px;">
                            <i class="bi bi-pencil-square"></i> Kelola Akun
                        </summary>
                        <div class="edit-details-content" style="margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 12px;">
                            <form method="POST" action="{{ route('pengguna.update', $manager->id_user) }}" class="room-actions" style="margin-top:0; flex-direction:column; gap:8px;">
                                @csrf
                                @method('PUT')
                                <div style="display: flex; flex-direction: column; gap: 8px; width: 100%; text-align: left;">
                                    <div>
                                        <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px; display: block;">Nama</label>
                                        <input type="text" name="nama" value="{{ $manager->nama }}" required style="height: 38px; font-size: 13px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px; display: block;">Username</label>
                                        <input type="text" name="username" value="{{ $manager->username }}" required style="height: 38px; font-size: 13px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px; display: block;">Email</label>
                                        <input type="email" name="email" value="{{ $manager->email }}" required style="height: 38px; font-size: 13px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px; display: block;">No Telpon</label>
                                        <input type="text" name="no_telpon" value="{{ $manager->no_telpon }}" placeholder="No telpon" style="height: 38px; font-size: 13px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px; display: block;">Password Baru (opsional)</label>
                                        <input type="password" name="password" placeholder="Password baru" style="height: 38px; font-size: 13px;">
                                    </div>
                                </div>
                                <button type="submit" class="primary-btn" style="width:100%; margin-top: 8px;">Update Akun</button>
                            </form>

                            <form method="POST" action="{{ route('pengguna.status', $manager->id_user) }}" style="margin-top:8px;">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status_akun" value="{{ $manager->status_akun === 'Aktif' ? 'Nonaktif' : 'Aktif' }}">
                                <button type="submit" class="ghost-btn" style="width:100%;">
                                    {{ $manager->status_akun === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }} Akun
                                </button>
                            </form>
                        </div>
                    </details>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
