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

{{-- Filter Laporan --}}
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
        <div>
            <label for="bulan">Pilih Bulan</label>
            <select id="bulan" name="bulan">
                @foreach ($bulanList as $key => $name)
                    <option value="{{ $key }}" {{ $filterBulan === $key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="primary-btn">Tampilkan</button>
            <a class="ghost-btn" href="{{ route('laporan.export', ['tahun' => $filterTahun, 'bulan' => $filterBulan]) }}">Export Excel</a>
            <a class="ghost-btn" href="{{ route('laporan.export-pdf', ['tahun' => $filterTahun, 'bulan' => $filterBulan]) }}" style="background-color: #ef4444; color: white; border: none; padding: 10px 16px; border-radius: 6px; text-decoration: none; display: inline-block; font-weight: 600; text-align: center;">Export PDF</a>
        </div>
    </form>
</section>

{{-- Tabel Ringkasan Bulanan / Rincian Tagihan --}}
<section class="room-group">
    <header>
        <div>
            <h3>
                @if ($filterBulan === 'all')
                    Ringkasan Pendapatan Bulanan
                @else
                    Rincian Tagihan Bulan {{ $bulanList[$filterBulan] }}
                @endif
            </h3>
            <p>Tahun {{ $filterTahun }}</p>
        </div>
    </header>

    @if ($filterBulan === 'all')
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
    @else
        @if ($details->isEmpty())
            <div class="empty-state" style="margin:12px;">Belum ada data tagihan untuk bulan {{ $bulanList[$filterBulan] }} {{ $filterTahun }}.</div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Penyewa</th>
                            <th>No Kamar</th>
                            <th>Periode</th>
                            <th>Nominal</th>
                            <th>Jatuh Tempo</th>
                            <th>Tanggal Bayar</th>
                            <th>Metode Pembayaran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $d)
                            <tr>
                                <td><strong>{{ $d->nama_penyewa }}</strong></td>
                                <td>{{ $d->no_kamar ?? 'Tanpa Kamar' }}</td>
                                <td>{{ $d->periode }}</td>
                                <td><strong>Rp {{ number_format($d->nominal, 0, ',', '.') }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($d->tanggal_jatuh_tempo)->translatedFormat('d-m-Y') }}</td>
                                <td>{{ $d->tanggal_bayar ? \Carbon\Carbon::parse($d->tanggal_bayar)->translatedFormat('d-m-Y') : '-' }}</td>
                                <td>{{ $d->metode_pembayaran ?? '-' }}</td>
                                <td>
                                    @if ($d->status === 'Lunas')
                                        <span class="badge badge-success" style="background-color: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;">Lunas</span>
                                    @elseif ($d->status === 'Belum Bayar')
                                        <span class="badge badge-warning" style="background-color: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;">Belum Bayar</span>
                                    @elseif ($d->status === 'Telat')
                                        <span class="badge badge-danger" style="background-color: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;">Telat</span>
                                    @else
                                        <span class="badge badge-info" style="background-color: #e0f2fe; color: #075985; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;">{{ $d->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#f1f5f9;font-weight:700;">
                            <td colspan="3">TOTAL PENDAPATAN (LUNAS)</td>
                            <td>Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endif
</section>
@endsection
