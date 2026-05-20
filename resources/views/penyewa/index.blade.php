@extends('layouts.app')

@section('title', 'Manajemen Penyewa - KosKu')

@section('content')
@if (session('success'))
    <div class="alert success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert error">{{ $errors->first() }}</div>
@endif

<section class="page-header">
    <div>
        <h2>Manajemen Penyewa</h2>
        <p>Check-in penyewa baru, edit data, dan proses checkout.</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <p>Total Penyewa</p>
        <h3>{{ $tenants->count() }}</h3>
    </article>
    <article class="stat-card">
        <p>Aktif</p>
        <h3>{{ $tenants->where('is_active', true)->count() }}</h3>
        <small class="text-success">Sedang menempati</small>
    </article>
    <article class="stat-card">
        <p>Sudah Checkout</p>
        <h3>{{ $tenants->where('is_active', false)->count() }}</h3>
        <small>Selesai kontrak</small>
    </article>
    <article class="stat-card">
        <p>Kamar Kosong</p>
        <h3>{{ $availableRooms->count() }}</h3>
        <small>Siap untuk check-in</small>
    </article>
</section>

{{-- Form Check-in --}}
<section class="filter-panel">
    <h3 class="section-title" style="margin-top:0;">Check-in Penyewa Baru</h3>
    <form method="POST" action="{{ route('penyewa.store') }}" enctype="multipart/form-data" class="form-grid">
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
            <select id="kontrak" name="kontrak" required>
                <option value="Bulanan" {{ old('kontrak') == 'Bulanan' ? 'selected' : '' }}>Bulanan</option>
                <option value="Tahunan" {{ old('kontrak') == 'Tahunan' ? 'selected' : '' }}>Tahunan</option>
                <option value="Mingguan" {{ old('kontrak') == 'Mingguan' ? 'selected' : '' }}>Mingguan</option>
                <option value="Harian" {{ old('kontrak') == 'Harian' ? 'selected' : '' }}>Harian</option>
            </select>
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
            <label for="dokumen_ktp">Dokumen KTP (Scan/Foto)</label>
            <input id="dokumen_ktp" type="file" name="dokumen_ktp" accept=".pdf,.jpg,.jpeg,.png">
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

        <div class="actions" style="grid-column: span 4;">
            <button type="submit" class="primary-btn" style="width: auto; min-width: 180px;">Simpan Check-in</button>
        </div>
    </form>
</section>

{{-- Data Penyewa --}}
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
                        @if ($tenant->is_active)
                            <span class="badge kosong">Aktif</span>
                        @else
                            <span class="badge maintenance">Selesai</span>
                        @endif
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

                    @if ($tenant->tanggal_selesai)
                        <div class="room-meta" style="background:#fef3c7;border-radius:10px;padding:10px;margin-top:10px;">
                            <small style="color:#92400e;">Checkout</small>
                            <p style="color:#92400e;">{{ $tenant->tanggal_selesai }}</p>
                        </div>
                    @endif

                    @if ($tenant->dokumen_ktp || $tenant->dokumen_kontrak)
                        <div class="room-actions" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                            @if ($tenant->dokumen_ktp)
                                <a class="ghost-btn" style="flex:1;font-size:12px;text-align:center;" target="_blank" href="{{ asset('storage/' . $tenant->dokumen_ktp) }}">📄 Dokumen KTP</a>
                            @endif
                            @if ($tenant->dokumen_kontrak)
                                <a class="ghost-btn" style="flex:1;font-size:12px;text-align:center;" target="_blank" href="{{ asset('storage/' . $tenant->dokumen_kontrak) }}">📋 Dokumen Kontrak</a>
                            @endif
                        </div>
                    @endif

                    {{-- Edit Form --}}
                    @if ($tenant->is_active)
                        <details class="edit-details" style="margin-top:10px;">
                            <summary class="ghost-btn" style="width:100%;cursor:pointer;font-size:12px;">
                                <i class="bi bi-pencil"></i> Edit Data
                            </summary>
                            <form method="POST" action="{{ route('penyewa.update', $tenant->id_penyewa) }}" style="margin-top:8px;">
                                @csrf
                                @method('PUT')
                                <div style="display:grid;gap:8px;">
                                    <input type="text" name="nama" value="{{ $tenant->nama }}" placeholder="Nama" required>
                                    <input type="text" name="ktp" value="{{ $tenant->ktp }}" placeholder="No KTP">
                                    <select name="kontrak" required>
                                        <option value="Bulanan" {{ $tenant->kontrak == 'Bulanan' ? 'selected' : '' }}>Bulanan</option>
                                        <option value="Tahunan" {{ $tenant->kontrak == 'Tahunan' ? 'selected' : '' }}>Tahunan</option>
                                        <option value="Mingguan" {{ $tenant->kontrak == 'Mingguan' ? 'selected' : '' }}>Mingguan</option>
                                        <option value="Harian" {{ $tenant->kontrak == 'Harian' ? 'selected' : '' }}>Harian</option>
                                    </select>
                                    <input type="date" name="tanggal_masuk" value="{{ $tenant->tanggal_masuk }}" required>
                                    <input type="date" name="tanggal_keluar" value="{{ $tenant->tanggal_keluar }}">
                                    <button type="submit" class="primary-btn" style="width:100%;">Simpan Perubahan</button>
                                </div>
                            </form>
                        </details>

                        {{-- Checkout --}}
                        <form method="POST" action="{{ route('penyewa.checkout', $tenant->id_penyewa) }}" onsubmit="return confirm('Yakin checkout penyewa {{ $tenant->nama }}? Kamar akan dibebaskan.');" style="margin-top:6px;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="ghost-btn" style="width:100%;border-color:#fecaca;color:#b91c1c;font-size:12px;">
                                <i class="bi bi-box-arrow-right"></i> Checkout
                            </button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
