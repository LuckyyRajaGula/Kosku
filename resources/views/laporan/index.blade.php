@extends('layouts.app')

@section('title', 'Laporan Keuangan - KosKu')

@section('content')
<section class="page-header">
    <div>
        <h2>Laporan Keuangan</h2>
        <p>Analisis pendapatan dan ringkasan pembayaran kost.</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <p>Total Pendapatan</p>
        <h3>Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</h3>
        <small>Tahun {{ $filterTahun }}</small>
    </article>
    <article class="stat-card">
        <p>Total Tagihan</p>
        <h3>{{ $totalTagihan }}</h3>
        <small>Semua tagihan tahun ini</small>
    </article>
    <article class="stat-card">
        <p>Tagihan Lunas</p>
        <h3>{{ $totalLunas }}</h3>
        <small class="text-success">Sudah dibayar</small>
    </article>
    <article class="stat-card">
        <p>Tagihan Belum Bayar</p>
        <h3>{{ $totalBelum }}</h3>
        <small class="text-warning">Menunggu pembayaran</small>
    </article>
</section>

{{-- Filter Tahun --}}
<section class="filter-panel">
    <form method="GET" action="{{ route('laporan') }}" class="filter-grid">
        <div>
            <label for="tahun">Pilih Tahun</label>
            <select id="tahun" name="tahun">
                @foreach ($tahunList as $t)
                    <option value="{{ $t }}" {{ $filterTahun === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="primary-btn">Tampilkan</button>
            <a class="ghost-btn" href="{{ route('laporan.export', ['tahun' => $filterTahun]) }}">Export Excel</a>
        </div>
    </form>
</section>

{{-- Tabel Ringkasan Bulanan --}}
<section class="room-group">
    <header>
        <div>
            <h3>Ringkasan Pendapatan Bulanan</h3>
            <p>Tahun {{ $filterTahun }}</p>
        </div>
    </header>

    @if ($bulanan->isEmpty())
        <div class="empty-state" style="margin:12px;">Belum ada data tagihan untuk tahun {{ $filterTahun }}.</div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Total Tagihan</th>
                        <th>Lunas</th>
                        <th>Belum Bayar</th>
                        <th>Telat</th>
                        <th>Pendapatan (Lunas)</th>
                        <th>Target (Total)</th>
                        <th>Rasio</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandPendapatan = 0; $grandTarget = 0; @endphp
                    @foreach ($bulanan as $b)
                        @php
                            $grandPendapatan += $b->pendapatan;
                            $grandTarget += $b->total_nominal;
                            $rasio = $b->total_nominal > 0 ? round(($b->pendapatan / $b->total_nominal) * 100) : 0;

                            // Nama bulan
                            $bulanArr = explode('-', $b->periode_bulan);
                            $namaBulan = \Carbon\Carbon::createFromDate($bulanArr[0], $bulanArr[1], 1)->translatedFormat('F Y');
                        @endphp
                        <tr>
                            <td><strong>{{ $namaBulan }}</strong></td>
                            <td>{{ $b->total_tagihan }}</td>
                            <td><span class="text-success" style="font-weight:700;">{{ $b->jumlah_lunas }}</span></td>
                            <td><span class="text-warning" style="font-weight:700;">{{ $b->jumlah_belum }}</span></td>
                            <td><span class="text-error" style="font-weight:700;">{{ $b->jumlah_telat }}</span></td>
                            <td><strong>Rp {{ number_format($b->pendapatan, 0, ',', '.') }}</strong></td>
                            <td>Rp {{ number_format($b->total_nominal, 0, ',', '.') }}</td>
                            <td>
                                <div class="mini-bar">
                                    <div class="mini-bar-fill" style="width: {{ $rasio }}%"></div>
                                </div>
                                <small style="font-weight:700;">{{ $rasio }}%</small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f1f5f9;font-weight:700;">
                        <td>TOTAL</td>
                        <td>{{ $bulanan->sum('total_tagihan') }}</td>
                        <td>{{ $bulanan->sum('jumlah_lunas') }}</td>
                        <td>{{ $bulanan->sum('jumlah_belum') }}</td>
                        <td>{{ $bulanan->sum('jumlah_telat') }}</td>
                        <td>Rp {{ number_format($grandPendapatan, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($grandTarget, 0, ',', '.') }}</td>
                        <td>{{ $grandTarget > 0 ? round(($grandPendapatan / $grandTarget) * 100) : 0 }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</section>
@endsection
