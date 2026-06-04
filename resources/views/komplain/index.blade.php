@extends('layouts.app')

@section('title', 'Manajemen Komplain - KosKu')

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
        <h2>Manajemen Komplain</h2>
        <p>{{ $role === 'penyewa' ? 'Ajukan dan pantau komplain Anda.' : 'Kelola komplain dan laporan dari penyewa.' }}</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card"><p>Total Komplain</p><h3>{{ $stats['total'] }}</h3></article>
    <article class="stat-card"><p>Diajukan</p><h3>{{ $stats['diajukan'] }}</h3><small class="text-warning">Menunggu</small></article>
    <article class="stat-card"><p>Diproses</p><h3>{{ $stats['diproses'] }}</h3><small style="color:#2563eb;">Sedang ditangani</small></article>
    <article class="stat-card"><p>Selesai</p><h3>{{ $stats['selesai'] }}</h3><small class="text-success">Sudah ditangani</small></article>
</section>

{{-- Filter --}}
<section class="filter-panel">
    <form method="GET" action="{{ route('komplain') }}" class="filter-grid">
        <div>
            <label for="status">Filter Status</label>
            <select id="status" name="status">
                <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                <option value="Diajukan" {{ $filterStatus === 'Diajukan' ? 'selected' : '' }}>Diajukan</option>
                <option value="Diproses" {{ $filterStatus === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                <option value="Selesai" {{ $filterStatus === 'Selesai' ? 'selected' : '' }}>Selesai</option>
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="primary-btn">Filter</button>
            <a href="{{ route('komplain') }}" class="ghost-btn">Reset</a>
        </div>
    </form>
</section>

{{-- Form Ajukan Komplain (penyewa) --}}
@if ($role === 'penyewa' && $penyewaId)
    <section class="filter-panel">
        <h3 class="section-title" style="margin-top:0;">Ajukan Komplain Baru</h3>
        <form method="POST" action="{{ route('komplain.store') }}" class="form-grid" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="jenis_komplain">Jenis Komplain</label>
                <select id="jenis_komplain" name="jenis_komplain" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="Kerusakan Fasilitas" {{ old('jenis_komplain') === 'Kerusakan Fasilitas' ? 'selected' : '' }}>Kerusakan Fasilitas</option>
                    <option value="Kebersihan" {{ old('jenis_komplain') === 'Kebersihan' ? 'selected' : '' }}>Kebersihan</option>
                    <option value="Keamanan" {{ old('jenis_komplain') === 'Keamanan' ? 'selected' : '' }}>Keamanan</option>
                    <option value="Kebisingan" {{ old('jenis_komplain') === 'Kebisingan' ? 'selected' : '' }}>Kebisingan</option>
                    <option value="Air/Listrik" {{ old('jenis_komplain') === 'Air/Listrik' ? 'selected' : '' }}>Air/Listrik</option>
                    <option value="Lainnya" {{ old('jenis_komplain') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>
            <div style="grid-column: span 2;">
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="3" required style="width:100%;border:1px solid #d8dce4;border-radius:14px;padding:12px;font-size:14px;font-family:inherit;resize:vertical;background:#f8f9fb;">{{ old('deskripsi') }}</textarea>
            </div>
            <div>
                <label for="bukti_foto">Foto Bukti (opsional)</label>
                <input id="bukti_foto" type="file" name="bukti_foto" accept=".jpg,.jpeg,.png">
            </div>
            <div class="actions">
                <button type="submit" class="primary-btn" style="width: 100%;">Kirim Komplain</button>
            </div>
        </form>
    </section>
@endif

{{-- Daftar Komplain --}}
@if ($komplainList->isEmpty())
    <section class="empty-state">
        <i class="bi bi-chat-left-text" style="font-size:32px;"></i>
        <p>Belum ada komplain{{ $role === 'penyewa' ? '. Ajukan komplain jika ada masalah.' : ' yang tercatat.' }}</p>
    </section>
@else
    <section class="room-group">
        <header>
            <div>
                <h3>Daftar Komplain</h3>
                <p>Total {{ $komplainList->count() }} komplain</p>
            </div>
        </header>

        <div class="room-grid">
            @foreach ($komplainList as $k)
                <article class="room-card">
                    <div class="room-head">
                        <strong>{{ $k->nama_penyewa }}</strong>
                        <span class="badge {{ strtolower($k->status_penanganan) }}">{{ $k->status_penanganan }}</span>
                    </div>
                    <p class="room-type">Kamar: {{ $k->no_kamar ?: '-' }}</p>

                    <div class="room-meta">
                        <small>Jenis</small>
                        <p>{{ $k->jenis_komplain }}</p>
                    </div>
                    <div class="room-meta">
                        <small>Deskripsi</small>
                        <p style="font-weight:400;">{{ $k->deskripsi }}</p>
                    </div>
                    <div class="room-meta">
                        <small>Foto Bukti</small>
                        @if ($k->bukti_foto)
                            <p><a class="ghost-btn" style="font-size:12px;padding:4px 10px;" target="_blank" href="{{ asset('storage/' . $k->bukti_foto) }}">📷 Lihat</a></p>
                        @else
                            <p style="font-weight:400; color:#94a3b8;">-</p>
                        @endif
                    </div>
                    <div class="room-meta">
                        <small>Tanggal Pengajuan</small>
                        <p>{{ $k->tanggal ?: '-' }}</p>
                    </div>

                    @if ($k->respon)
                        <div class="room-meta" style="background:#f0fdf4;border-radius:10px;padding:10px;margin-top:10px;">
                            <small style="color:#166534;">Respon Pengelola</small>
                            <p style="font-weight:400;color:#166534;">{{ $k->respon }}</p>
                        </div>
                    @endif

                    @if ($k->tanggal_selesai)
                        <div class="room-meta">
                            <small>Tanggal Selesai</small>
                            <p>{{ $k->tanggal_selesai }}</p>
                        </div>
                    @endif

                    {{-- Aksi pengelola/pemilik --}}
                    @if (in_array($role, ['pemilik', 'pengelola']))
                        <details class="edit-details" style="margin-top: 10px; width: 100%;">
                            <summary class="ghost-btn" style="width: 100%; min-height: 36px; font-size: 13px; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 6px;">
                                <i class="bi bi-pencil-square"></i> Tangani Komplain
                            </summary>
                            <div class="edit-details-content" style="margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 12px;">
                                <form method="POST" action="{{ route('komplain.update', $k->id_komplain) }}" class="room-actions" style="margin-top:0;flex-direction:column;gap:8px;">
                                    @csrf
                                    @method('PUT')
                                    <div style="display: flex; flex-direction: column; gap: 8px; width: 100%; text-align: left;">
                                        <div>
                                            <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px; display: block;">Status Penanganan</label>
                                            <select name="status_penanganan" required style="height: 38px; font-size: 13px;">
                                                <option value="Diajukan" {{ $k->status_penanganan === 'Diajukan' ? 'selected' : '' }}>Diajukan</option>
                                                <option value="Diproses" {{ $k->status_penanganan === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                                                <option value="Selesai" {{ $k->status_penanganan === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: bold; margin-bottom: 2px; display: block;">Respon Pengelola</label>
                                            <textarea name="respon" rows="2" placeholder="Tulis respon..." style="width:100%;border:1px solid #d8dce4;border-radius:12px;padding:10px;font-size:13px;font-family:inherit;resize:vertical;background:#f8f9fb;">{{ $k->respon }}</textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="primary-btn" style="width:100%; margin-top: 8px;">Update Status</button>
                                </form>

                                <form method="POST" action="{{ route('komplain.delete', $k->id_komplain) }}" onsubmit="return confirm('Yakin hapus komplain ini?');" style="margin-top:8px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ghost-btn" style="width:100%;border-color:#fecaca;color:#b91c1c;">Hapus</button>
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
