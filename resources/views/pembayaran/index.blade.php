@extends('layouts.app')

@section('title', 'Manajemen Pembayaran - KosKu')

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

@php $role = $user['role'] ?? 'penyewa'; @endphp

<section class="page-header">
    <div>
        <h2>{{ $role === 'penyewa' ? 'Tagihan & Pembayaran Saya' : 'Manajemen Pembayaran' }}</h2>
        <p>{{ $role === 'penyewa' ? 'Lihat tagihan dan unggah bukti pembayaran.' : 'Catat dan pantau pembayaran sewa kost.' }}</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card"><p>Total Tagihan</p><h3>{{ $stats['total'] }}</h3></article>
    <article class="stat-card"><p>Lunas</p><h3>{{ $stats['lunas'] }}</h3><small class="text-success">Sudah dibayar</small></article>
    <article class="stat-card"><p>Belum Bayar</p><h3>{{ $stats['belum'] }}</h3><small class="text-warning">Menunggu pembayaran</small></article>
    <article class="stat-card"><p>Total Pendapatan</p><h3>Rp {{ number_format($stats['totalNominal'], 0, ',', '.') }}</h3><small>Dari tagihan lunas</small></article>
</section>

{{-- Filter --}}
<section class="filter-panel">
    <form method="GET" action="{{ route('pembayaran') }}" class="filter-grid">
        <div>
            <label for="status">Filter Status</label>
            <select id="status" name="status">
                <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>Semua</option>
                <option value="Belum Bayar" {{ $filterStatus === 'Belum Bayar' ? 'selected' : '' }}>Belum Bayar</option>
                <option value="Menunggu Verifikasi" {{ $filterStatus === 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                <option value="Lunas" {{ $filterStatus === 'Lunas' ? 'selected' : '' }}>Lunas</option>
                <option value="Telat" {{ $filterStatus === 'Telat' ? 'selected' : '' }}>Telat</option>
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="primary-btn">Filter</button>
            <a href="{{ route('pembayaran') }}" class="ghost-btn">Reset</a>
        </div>
    </form>
</section>

{{-- Form Buat Tagihan (hanya pengelola/pemilik) --}}
@if (in_array($role, ['pemilik', 'pengelola']))
    <section class="filter-panel">
        <h3 class="section-title" style="margin-top:0;">Buat Tagihan Baru</h3>
        <form method="POST" action="{{ route('pembayaran.store') }}" class="form-grid">
            @csrf
            <div>
                <label for="id_penyewa">Penyewa</label>
                <select id="id_penyewa" name="id_penyewa" required>
                    <option value="">-- Pilih Penyewa --</option>
                    @foreach ($penyewaAktif as $p)
                        <option value="{{ $p->id_penyewa }}" data-harga="{{ $p->harga }}" data-tanggal-masuk="{{ $p->tanggal_masuk }}" {{ old('id_penyewa') == $p->id_penyewa ? 'selected' : '' }}>
                            {{ $p->nama }} ({{ $p->no_kamar ?: 'Tanpa Kamar' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="periode">Periode</label>
                <input id="periode" type="text" name="periode" value="{{ old('periode') }}" placeholder="contoh: Mei 2026" required>
            </div>
            <div>
                <label for="nominal">Nominal (Rp)</label>
                <input id="nominal" type="number" name="nominal" value="{{ old('nominal') }}" min="0" required>
            </div>
            <div>
                <label for="tanggal_jatuh_tempo">Jatuh Tempo</label>
                <input id="tanggal_jatuh_tempo" type="date" name="tanggal_jatuh_tempo" value="{{ old('tanggal_jatuh_tempo') }}" required>
            </div>
            <div class="actions" style="grid-column: span 4;">
                <button type="submit" class="primary-btn" style="width: auto; min-width: 160px;">Buat Tagihan</button>
            </div>
        </form>
    </section>
@endif

{{-- Daftar Tagihan --}}
<section class="room-group">
    <header>
        <div>
            <h3>Daftar Tagihan</h3>
            <p>Total {{ $tagihan->count() }} tagihan</p>
        </div>
    </header>

    @if ($tagihan->isEmpty())
        <div class="empty-state" style="margin:12px;">Belum ada data tagihan.</div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        @if ($role !== 'penyewa')
                            <th>Penyewa</th>
                            <th>Kamar</th>
                        @endif
                        <th>Periode</th>
                        <th>Nominal</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Tgl Bayar</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tagihan as $t)
                        <tr>
                            @if ($role !== 'penyewa')
                                <td><strong>{{ $t->nama_penyewa }}</strong></td>
                                <td>{{ $t->no_kamar ?: '-' }}</td>
                            @endif
                            <td>{{ $t->periode }}</td>
                            <td>Rp {{ number_format($t->nominal, 0, ',', '.') }}</td>
                            <td>{{ $t->tanggal_jatuh_tempo }}</td>
                            <td>
                                @php
                                    $badgeClass = match($t->status) {
                                        'Lunas' => 'lunas',
                                        'Telat' => 'telat',
                                        'Menunggu Verifikasi' => 'menunggu-verifikasi',
                                        default => 'belum-bayar',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $t->status }}</span>
                            </td>
                            <td>{{ $t->tanggal_bayar ?: '-' }}</td>
                            <td>
                                @if ($t->bukti_bayar)
                                    <a href="{{ asset('storage/' . $t->bukti_bayar) }}" target="_blank" class="ghost-btn" style="font-size:12px;padding:4px 10px;">📎 Lihat</a>
                                @else
                                    <span style="color:#94a3b8;font-size:13px;">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    {{-- === PENYEWA: Upload Bukti Bayar === --}}
                                    @if ($role === 'penyewa')
                                        @if (in_array($t->status, ['Belum Bayar', 'Telat']))
                                            <form method="POST" action="{{ route('pembayaran.upload-bukti', $t->id_tagihan) }}" enctype="multipart/form-data" class="inline-form">
                                                @csrf
                                                <input type="file" name="bukti_bayar" accept=".pdf,.jpg,.jpeg,.png" required style="width:140px;">
                                                <button type="submit" class="primary-btn soft">Upload Bukti</button>
                                            </form>
                                        @elseif ($t->status === 'Menunggu Verifikasi')
                                            <span class="badge menunggu-verifikasi" style="font-size:12px;">⏳ Menunggu verifikasi pengelola</span>
                                        @elseif ($t->status === 'Lunas')
                                            <span class="text-success" style="font-size:13px;font-weight:700;">✓ Lunas</span>
                                        @endif
                                    @endif

                                    {{-- === PENGELOLA/PEMILIK: Validasi & Kelola === --}}
                                    @if (in_array($role, ['pemilik', 'pengelola']))
                                        @if ($t->status === 'Menunggu Verifikasi')
                                            {{-- Tombol Tandai Lunas instan --}}
                                            <form method="POST" action="{{ route('pembayaran.tandai-lunas', $t->id_tagihan) }}" onsubmit="return confirm('Tandai tagihan {{ $t->periode }} ({{ $t->nama_penyewa }}) sebagai LUNAS?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="primary-btn" style="background:linear-gradient(135deg,#059669,#10b981);font-size:12px;">
                                                    ✓ Tandai Lunas
                                                </button>
                                            </form>
                                        @endif

                                        @if ($t->status !== 'Lunas')
                                            {{-- Form update manual lengkap --}}
                                            <details class="edit-details" style="margin:0;">
                                                <summary class="ghost-btn" style="font-size:12px;cursor:pointer;padding:4px 10px;">
                                                    ✏️ Edit Manual
                                                </summary>
                                                <form method="POST" action="{{ route('pembayaran.update', $t->id_tagihan) }}" enctype="multipart/form-data" style="margin-top:8px;display:grid;gap:6px;">
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="status" required style="font-size:13px;">
                                                        <option value="Belum Bayar" {{ $t->status === 'Belum Bayar' ? 'selected' : '' }}>Belum Bayar</option>
                                                        <option value="Menunggu Verifikasi" {{ $t->status === 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                                                        <option value="Lunas" {{ $t->status === 'Lunas' ? 'selected' : '' }}>Lunas</option>
                                                        <option value="Telat" {{ $t->status === 'Telat' ? 'selected' : '' }}>Telat</option>
                                                    </select>
                                                    <input type="text" name="metode_pembayaran" value="{{ $t->metode_pembayaran }}" placeholder="Metode (Transfer/Cash)" style="font-size:13px;">
                                                    <input type="date" name="tanggal_bayar" value="{{ $t->tanggal_bayar }}" style="font-size:13px;">
                                                    <input type="file" name="bukti_bayar" accept=".pdf,.jpg,.jpeg,.png" style="font-size:12px;">
                                                    <button type="submit" class="primary-btn soft" style="font-size:12px;">Update</button>
                                                </form>
                                            </details>
                                        @else
                                            <span class="text-success" style="font-size:13px;font-weight:700;">✓ Lunas</span>
                                        @endif

                                        {{-- Hapus (hanya pemilik) --}}
                                        @if ($role === 'pemilik')
                                            <form method="POST" action="{{ route('pembayaran.delete', $t->id_tagihan) }}" onsubmit="return confirm('Yakin hapus tagihan ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ghost-btn" style="border-color:#fecaca;color:#b91c1c;font-size:12px;padding:4px 10px;">Hapus</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
