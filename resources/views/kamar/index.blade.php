@extends('layouts.app')

@section('title', 'Manajemen Kamar - KosKu')

@section('content')
@if (session('success'))
    <div class="alert success">{{ session('success') }}</div>
@endif

<section class="page-header">
    <div>
        <h2>Manajemen Kamar</h2>
        <p>Kelola dan pantau status kamar kost.</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card"><p>Total Kamar</p><h3>{{ $stats['total'] }}</h3></article>
    <article class="stat-card"><p>Terisi</p><h3>{{ $stats['terisi'] }}</h3></article>
    <article class="stat-card"><p>Kosong</p><h3>{{ $stats['kosong'] }}</h3></article>
    <article class="stat-card"><p>Maintenance</p><h3>{{ $stats['maintenance'] }}</h3></article>
</section>

<section class="filter-panel">
    <form method="GET" action="{{ route('kamar') }}" class="filter-grid">
        <div>
            <label for="q">Pencarian</label>
            <input id="q" type="text" name="q" value="{{ $q }}" placeholder="Cari kamar, properti, atau penyewa">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                <option value="Kosong" {{ $status === 'Kosong' ? 'selected' : '' }}>Kosong</option>
                <option value="Terisi" {{ $status === 'Terisi' ? 'selected' : '' }}>Terisi</option>
                <option value="Maintenance" {{ $status === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="primary-btn">Filter</button>
            <a href="{{ route('kamar') }}" class="ghost-btn">Reset</a>
        </div>
    </form>
</section>

@if ($canEdit)
    <section class="filter-panel">
        <h3 class="section-title" style="margin-top:0;">Tambah Kamar</h3>
        <form method="POST" action="{{ route('kamar.store') }}" class="filter-grid">
            @csrf
            <div>
                <label for="no_kamar">Nomor Kamar</label>
                <input id="no_kamar" type="text" name="no_kamar" required>
            </div>
            <div>
                <label for="tipe_kamar">Tipe</label>
                <input id="tipe_kamar" type="text" name="tipe_kamar">
            </div>
            <div>
                <label for="harga">Harga</label>
                <input id="harga" type="number" name="harga" min="0" required>
            </div>
            <div>
                <label for="status_ketersediaan">Status</label>
                <select id="status_ketersediaan" name="status_ketersediaan" required>
                    <option value="Kosong">Kosong</option>
                    <option value="Terisi">Terisi</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>
            <div>
                <label for="luas_kamar">Luas Kamar</label>
                <input id="luas_kamar" type="text" name="luas_kamar" placeholder="contoh: 3x4 m">
            </div>
            <div>
                <label for="fasilitias">Fasilitas</label>
                <input id="fasilitias" type="text" name="fasilitias" placeholder="AC, WiFi, TV">
            </div>
            <div class="actions">
                <button type="submit" class="primary-btn">Simpan Kamar</button>
            </div>
        </form>
    </section>
@endif

@if ($rooms->isEmpty())
    <section class="empty-state">
        <i class="bi bi-inboxes"></i>
        <p>Tidak ada kamar yang cocok dengan filter saat ini.</p>
    </section>
@else
    <section class="room-group">
        <header>
            <div>
                <h3>Data Kamar</h3>
                <p>Total {{ $rooms->count() }} kamar</p>
            </div>
        </header>

        <div class="room-grid">
            @foreach ($rooms as $room)
                <article class="room-card">
                    <div class="room-head">
                        <strong>{{ $room['no_kamar'] }}</strong>
                        <span class="badge {{ strtolower($room['status_ketersediaan']) }}">{{ $room['status_ketersediaan'] }}</span>
                    </div>
                    <p class="room-type">{{ $room['tipe_kamar'] ?: '-' }}</p>
                    <p class="room-price">Rp {{ number_format($room['harga'], 0, ',', '.') }} / bulan</p>

                    <div class="room-meta">
                        <small>Luas Kamar</small>
                        <p>{{ $room['luas_kamar'] ?: '-' }}</p>
                    </div>

                    <div class="fasilitas-list">
                        @forelse ($room['fasilitas_list'] as $fasilitas)
                            <span>{{ $fasilitas }}</span>
                        @empty
                            <span>-</span>
                        @endforelse
                    </div>

                    @if ($canEdit)
                        <form method="POST" action="{{ route('kamar.update', $room['id_kamar']) }}" class="room-actions" style="margin-top: 12px; flex-direction:column;">
                            @csrf
                            @method('PUT')
                            <input type="text" name="no_kamar" value="{{ $room['no_kamar'] }}" required>
                            <input type="text" name="tipe_kamar" value="{{ $room['tipe_kamar'] }}">
                            <input type="number" name="harga" value="{{ (int) $room['harga'] }}" min="0" required>
                            <select name="status_ketersediaan" required>
                                <option value="Kosong" {{ $room['status_ketersediaan'] === 'Kosong' ? 'selected' : '' }}>Kosong</option>
                                <option value="Terisi" {{ $room['status_ketersediaan'] === 'Terisi' ? 'selected' : '' }}>Terisi</option>
                                <option value="Maintenance" {{ $room['status_ketersediaan'] === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                            <input type="text" name="luas_kamar" value="{{ $room['luas_kamar'] }}" placeholder="Luas kamar">
                            <input type="text" name="fasilitias" value="{{ $room['fasilitias'] }}" placeholder="Fasilitas dipisah koma">
                            <button type="submit" class="primary-btn" style="width:100%;">Update</button>
                        </form>

                        <form method="POST" action="{{ route('kamar.delete', $room['id_kamar']) }}" onsubmit="return confirm('Yakin ingin menghapus kamar ini?');" style="margin-top:8px;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ghost-btn" style="width:100%; border-color:#fecaca; color:#b91c1c;">Hapus</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif
@endsection
