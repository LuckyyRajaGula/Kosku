@extends('layouts.app')

@section('title', 'Manajemen Kamar - KosKu')

@section('content')
@if (session('success'))
    <div class="alert success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert error">{{ session('error') }}</div>
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
        <form method="POST" action="{{ route('kamar.store') }}" class="form-grid">
            @csrf
            <div>
                <label for="no_kamar">Nomor Kamar</label>
                <input id="no_kamar" type="text" name="no_kamar" required>
            </div>
            <div>
                <label for="tipe_kamar">Tipe Kamar</label>
                <select id="tipe_kamar" name="tipe_kamar" required>
                    <option value="Ekonomis">Ekonomis</option>
                    <option value="Menengah">Menengah</option>
                    <option value="Eksklusif">Eksklusif</option>
                    <option value="Mewah">Mewah / Suite</option>
                </select>
            </div>
            <div>
                <label for="harga">Harga (Kategori Kos Indonesia)</label>
                <select id="harga" name="harga" required>
                    <optgroup label="Ekonomis (Sederhana / Fan / shared bathroom)">
                        <option value="500000">Rp 500.000 / bulan</option>
                        <option value="750000">Rp 750.000 / bulan</option>
                        <option value="1000000">Rp 1.000.000 / bulan</option>
                    </optgroup>
                    <optgroup label="Menengah (Standard/Deluxe - AC + Kamar Mandi Dalam)">
                        <option value="1200000">Rp 1.200.000 / bulan</option>
                        <option value="1500000" selected>Rp 1.500.000 / bulan</option>
                        <option value="1800000">Rp 1.800.000 / bulan</option>
                        <option value="2000000">Rp 2.000.000 / bulan</option>
                    </optgroup>
                    <optgroup label="Eksklusif (VIP - AC + Private Bath + TV + Fridge)">
                        <option value="2500000">Rp 2.500.000 / bulan</option>
                        <option value="3000000">Rp 3.000.000 / bulan</option>
                        <option value="3500000">Rp 3.500.000 / bulan</option>
                        <option value="4000000">Rp 4.000.000 / bulan</option>
                    </optgroup>
                    <optgroup label="Mewah / Suite (Luxury Co-Living / Serviced)">
                        <option value="5000000">Rp 5.000.000 / bulan</option>
                        <option value="6000000">Rp 6.000.000 / bulan</option>
                        <option value="7500000">Rp 7.500.000 / bulan</option>
                    </optgroup>
                </select>
            </div>
            <div>
                <label for="status_ketersediaan">Status</label>
                <select id="status_ketersediaan" name="status_ketersediaan" required>
                    <option value="Kosong" selected>Kosong</option>
                    <option value="Terisi">Terisi</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>
            <div>
                <label for="luas_kamar">Luas Kamar</label>
                <input id="luas_kamar" type="text" name="luas_kamar" placeholder="contoh: 3x4 m">
            </div>
            <div style="grid-column: span 2;">
                <label>Fasilitas</label>
                <div class="checkbox-group" style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; margin-bottom: 0; cursor: pointer;">
                        <input type="checkbox" name="fasilitias[]" value="AC" style="width: auto; height: auto;"> AC
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; margin-bottom: 0; cursor: pointer;">
                        <input type="checkbox" name="fasilitias[]" value="WiFi" style="width: auto; height: auto;"> WiFi
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; margin-bottom: 0; cursor: pointer;">
                        <input type="checkbox" name="fasilitias[]" value="TV" style="width: auto; height: auto;"> TV
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; margin-bottom: 0; cursor: pointer;">
                        <input type="checkbox" name="fasilitias[]" value="Kamar Mandi Dalam" style="width: auto; height: auto;"> Kamar Mandi Dalam
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; margin-bottom: 0; cursor: pointer;">
                        <input type="checkbox" name="fasilitias[]" value="Kulkas" style="width: auto; height: auto;"> Kulkas
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; margin-bottom: 0; cursor: pointer;">
                        <input type="checkbox" name="fasilitias[]" value="Balkon" style="width: auto; height: auto;"> Balkon
                    </label>
                </div>
            </div>
            <div class="actions" style="grid-column: 1;">
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
                        <details class="edit-details" style="margin-top: 10px; width: 100%;">
                            <summary class="ghost-btn" style="width: 100%; min-height: 36px; font-size: 13px; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 6px;">
                                <i class="bi bi-pencil-square"></i> Kelola Kamar
                            </summary>
                            <div class="edit-details-content" style="margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 12px;">
                                <form method="POST" action="{{ route('kamar.update', $room['id_kamar']) }}" class="room-actions" style="margin-top: 0; flex-direction:column;">
                                    @csrf
                                    @method('PUT')
                                    <div style="display: flex; flex-direction: column; gap: 8px; width: 100%;">
                                        <div>
                                            <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">Nomor Kamar</label>
                                            <input type="text" name="no_kamar" value="{{ $room['no_kamar'] }}" required style="height: 38px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">Tipe Kamar</label>
                                            <select name="tipe_kamar" required style="height: 38px; font-size: 13px;">
                                                <option value="Ekonomis" {{ $room['tipe_kamar'] === 'Ekonomis' ? 'selected' : '' }}>Ekonomis</option>
                                                <option value="Menengah" {{ $room['tipe_kamar'] === 'Menengah' ? 'selected' : '' }}>Menengah</option>
                                                <option value="Eksklusif" {{ $room['tipe_kamar'] === 'Eksklusif' ? 'selected' : '' }}>Eksklusif</option>
                                                <option value="Mewah" {{ $room['tipe_kamar'] === 'Mewah' ? 'selected' : '' }}>Mewah / Suite</option>
                                                @if (!in_array($room['tipe_kamar'], ['Ekonomis', 'Menengah', 'Eksklusif', 'Mewah']) && $room['tipe_kamar'])
                                                    <option value="{{ $room['tipe_kamar'] }}" selected>{{ $room['tipe_kamar'] }} (Lama)</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">Harga</label>
                                            <select name="harga" required style="height: 38px; font-size: 13px;">
                                                <optgroup label="Ekonomis">
                                                    <option value="500000" {{ (int)$room['harga'] === 500000 ? 'selected' : '' }}>Rp 500.000 / bulan</option>
                                                    <option value="750000" {{ (int)$room['harga'] === 750000 ? 'selected' : '' }}>Rp 750.000 / bulan</option>
                                                    <option value="1000000" {{ (int)$room['harga'] === 1000000 ? 'selected' : '' }}>Rp 1.000.000 / bulan</option>
                                                </optgroup>
                                                <optgroup label="Menengah">
                                                    <option value="1200000" {{ (int)$room['harga'] === 1200000 ? 'selected' : '' }}>Rp 1.200.000 / bulan</option>
                                                    <option value="1500000" {{ (int)$room['harga'] === 1500000 ? 'selected' : '' }}>Rp 1.500.000 / bulan</option>
                                                    <option value="1800000" {{ (int)$room['harga'] === 1800000 ? 'selected' : '' }}>Rp 1.800.000 / bulan</option>
                                                    <option value="2000000" {{ (int)$room['harga'] === 2000000 ? 'selected' : '' }}>Rp 2.000.000 / bulan</option>
                                                </optgroup>
                                                <optgroup label="Eksklusif">
                                                    <option value="2500000" {{ (int)$room['harga'] === 2500000 ? 'selected' : '' }}>Rp 2.500.000 / bulan</option>
                                                    <option value="3000000" {{ (int)$room['harga'] === 3000000 ? 'selected' : '' }}>Rp 3.000.000 / bulan</option>
                                                    <option value="3500000" {{ (int)$room['harga'] === 3500000 ? 'selected' : '' }}>Rp 3.500.000 / bulan</option>
                                                    <option value="4000000" {{ (int)$room['harga'] === 4000000 ? 'selected' : '' }}>Rp 4.000.000 / bulan</option>
                                                </optgroup>
                                                <optgroup label="Mewah">
                                                    <option value="5000000" {{ (int)$room['harga'] === 5000000 ? 'selected' : '' }}>Rp 5.000.000 / bulan</option>
                                                    <option value="6000000" {{ (int)$room['harga'] === 6000000 ? 'selected' : '' }}>Rp 6.000.000 / bulan</option>
                                                    <option value="7500000" {{ (int)$room['harga'] === 7500000 ? 'selected' : '' }}>Rp 7.500.000 / bulan</option>
                                                </optgroup>
                                                @if (!in_array((int)$room['harga'], [500000, 750000, 1000000, 1200000, 1500000, 1800000, 2000000, 2500000, 3000000, 3500000, 4000000, 5000000, 6000000, 7500000]))
                                                    <option value="{{ (int)$room['harga'] }}" selected>Custom (Rp {{ number_format($room['harga'], 0, ',', '.') }} / bulan)</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">Status</label>
                                            <select name="status_ketersediaan" required style="height: 38px; font-size: 13px;">
                                                <option value="Kosong" {{ $room['status_ketersediaan'] === 'Kosong' ? 'selected' : '' }}>Kosong</option>
                                                <option value="Terisi" {{ $room['status_ketersediaan'] === 'Terisi' ? 'selected' : '' }}>Terisi</option>
                                                <option value="Maintenance" {{ $room['status_ketersediaan'] === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">Luas Kamar</label>
                                            <input type="text" name="luas_kamar" value="{{ $room['luas_kamar'] }}" placeholder="Luas kamar" style="height: 38px; font-size: 13px;">
                                        </div>
                                        <div style="text-align: left; margin: 4px 0;">
                                            <label style="font-size: 11px; font-weight: bold;">Fasilitas</label>
                                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; margin-top: 4px;">
                                                @php
                                                    $checkedList = $room['fasilitas_list']->toArray();
                                                @endphp
                                                <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                                    <input type="checkbox" name="fasilitias[]" value="AC" {{ in_array('AC', $checkedList) ? 'checked' : '' }} style="width: auto; height: auto;"> AC
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                                    <input type="checkbox" name="fasilitias[]" value="WiFi" {{ in_array('WiFi', $checkedList) ? 'checked' : '' }} style="width: auto; height: auto;"> WiFi
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                                    <input type="checkbox" name="fasilitias[]" value="TV" {{ in_array('TV', $checkedList) ? 'checked' : '' }} style="width: auto; height: auto;"> TV
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                                    <input type="checkbox" name="fasilitias[]" value="Kamar Mandi Dalam" {{ in_array('Kamar Mandi Dalam', $checkedList) ? 'checked' : '' }} style="width: auto; height: auto;"> Km. Mandi
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                                    <input type="checkbox" name="fasilitias[]" value="Kulkas" {{ in_array('Kulkas', $checkedList) ? 'checked' : '' }} style="width: auto; height: auto;"> Kulkas
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                                    <input type="checkbox" name="fasilitias[]" value="Balkon" {{ in_array('Balkon', $checkedList) ? 'checked' : '' }} style="width: auto; height: auto;"> Balkon
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="primary-btn" style="width:100%; margin-top: 8px;">Update</button>
                                </form>

                                <form method="POST" action="{{ route('kamar.delete', $room['id_kamar']) }}" onsubmit="return confirm('Yakin ingin menghapus kamar ini?');" style="margin-top:8px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ghost-btn" style="width:100%; border-color:#fecaca; color:#b91c1c;">Hapus</button>
                                </form>
                            </div>
                        </details>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif
@endsection
