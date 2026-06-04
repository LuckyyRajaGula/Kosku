<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan - KosKu</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            padding: 0;
            vertical-align: middle;
        }
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: -0.5px;
        }
        .report-title {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
        }
        .meta-section {
            margin-bottom: 20px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 4px 0;
            font-size: 11px;
        }
        .meta-label {
            font-weight: bold;
            color: #64748b;
            width: 100px;
        }
        .meta-val {
            color: #0f172a;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .stats-table td {
            width: 25%;
            padding: 0 6px;
        }
        .stats-table td:first-child {
            padding-left: 0;
        }
        .stats-table td:last-child {
            padding-right: 0;
        }
        .stat-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
        }
        .stat-card .label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .stat-card .value {
            font-size: 15px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .stat-card .subtext {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 3px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 7px 10px;
            font-size: 10px;
            border: 1px solid #1e3a8a;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            color: #334155;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table .total-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-lunas {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-belum {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-telat {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-verifikasi {
            background-color: #e0f2fe;
            color: #075985;
        }
        .progress-bar {
            background-color: #e2e8f0;
            height: 6px;
            width: 60px;
            border-radius: 3px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 5px;
        }
        .progress-fill {
            background-color: #3b82f6;
            height: 6px;
            border-radius: 3px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <span class="logo-text">KosKu</span>
                </td>
                <td>
                    <div class="report-title">Laporan Keuangan</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="meta-section">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Periode Laporan:</td>
                <td class="meta-val">
                    @if ($bulan === 'all')
                        Tahun {{ $tahun }} (Semua Bulan)
                    @else
                        {{ $namaBulan }} {{ $tahun }}
                    @endif
                </td>
                <td class="meta-label" style="text-align: right; width: 100px;">Tanggal Cetak:</td>
                <td class="meta-val" style="text-align: right;">{{ now()->translatedFormat('d F Y') }}</td>
            </tr>
        </table>
    </div>

    <table class="stats-table">
        <tr>
            <td>
                <div class="stat-card">
                    <div class="label">Total Pendapatan</div>
                    <div class="value">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</div>
                    <div class="subtext">Status pembayaran lunas</div>
                </div>
            </td>
            <td>
                <div class="stat-card">
                    <div class="label">Total Tagihan</div>
                    <div class="value">{{ $totalTagihan }}</div>
                    <div class="subtext">Seluruh tagihan periode ini</div>
                </div>
            </td>
            <td>
                <div class="stat-card">
                    <div class="label">Tagihan Lunas</div>
                    <div class="value">{{ $totalLunas }}</div>
                    <div class="subtext">Berhasil diterima</div>
                </div>
            </td>
            <td>
                <div class="stat-card">
                    <div class="label">Belum Terbayar</div>
                    <div class="value">{{ $totalBelum }}</div>
                    <div class="subtext">Menunggu/keterlambatan</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">
        @if ($bulan === 'all')
            Ringkasan Pendapatan Bulanan
        @else
            Rincian Pembayaran Tagihan
        @endif
    </div>

    @if ($bulan === 'all')
        <table class="data-table">
            <thead>
                <tr>
                    <th>Periode</th>
                    <th style="text-align: center;">Total Tagihan</th>
                    <th style="text-align: center;">Lunas</th>
                    <th style="text-align: center;">Belum Bayar</th>
                    <th style="text-align: center;">Telat</th>
                    <th style="text-align: right;">Pendapatan (Lunas)</th>
                    <th style="text-align: right;">Target (Total)</th>
                    <th style="text-align: center;">Rasio</th>
                </tr>
            </thead>
            <tbody>
                @php $grandPendapatan = 0; $grandTarget = 0; @endphp
                @foreach ($bulanan as $b)
                    @php
                        $grandPendapatan += $b->pendapatan;
                        $grandTarget += $b->total_nominal;
                        $rasio = $b->total_nominal > 0 ? round(($b->pendapatan / $b->total_nominal) * 100) : 0;
                        $bulanArr = explode('-', $b->periode_bulan);
                        $namaBulanRow = \Carbon\Carbon::createFromDate($bulanArr[0], $bulanArr[1], 1)->translatedFormat('F Y');
                    @endphp
                    <tr>
                        <td><strong>{{ $namaBulanRow }}</strong></td>
                        <td style="text-align: center;">{{ $b->total_tagihan }}</td>
                        <td style="text-align: center; color: #065f46;">{{ $b->jumlah_lunas }}</td>
                        <td style="text-align: center; color: #92400e;">{{ $b->jumlah_belum }}</td>
                        <td style="text-align: center; color: #991b1b;">{{ $b->jumlah_telat }}</td>
                        <td style="text-align: right;"><strong>Rp {{ number_format($b->pendapatan, 0, ',', '.') }}</strong></td>
                        <td style="text-align: right;">Rp {{ number_format($b->total_nominal, 0, ',', '.') }}</td>
                        <td style="text-align: center;">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ $rasio }}%"></div>
                            </div>
                            <span style="font-weight: bold; font-size: 9px;">{{ $rasio }}%</span>
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td style="text-align: center;">{{ $bulanan->sum('total_tagihan') }}</td>
                    <td style="text-align: center;">{{ $bulanan->sum('jumlah_lunas') }}</td>
                    <td style="text-align: center;">{{ $bulanan->sum('jumlah_belum') }}</td>
                    <td style="text-align: center;">{{ $bulanan->sum('jumlah_telat') }}</td>
                    <td style="text-align: right;">Rp {{ number_format($grandPendapatan, 0, ',', '.') }}</td>
                    <td style="text-align: right;">Rp {{ number_format($grandTarget, 0, ',', '.') }}</td>
                    <td style="text-align: center;">{{ $grandTarget > 0 ? round(($grandPendapatan / $grandTarget) * 100) : 0 }}%</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Penyewa</th>
                    <th style="text-align: center;">No Kamar</th>
                    <th style="text-align: center;">Periode</th>
                    <th style="text-align: right;">Nominal</th>
                    <th style="text-align: center;">Jatuh Tempo</th>
                    <th style="text-align: center;">Tanggal Bayar</th>
                    <th style="text-align: center;">Metode</th>
                    <th style="text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($details as $d)
                    <tr>
                        <td><strong>{{ $d->nama_penyewa }}</strong></td>
                        <td style="text-align: center;">{{ $d->no_kamar ?? 'Tanpa Kamar' }}</td>
                        <td style="text-align: center;">{{ $d->periode }}</td>
                        <td style="text-align: right;">Rp {{ number_format($d->nominal, 0, ',', '.') }}</td>
                        <td style="text-align: center;">{{ \Carbon\Carbon::parse($d->tanggal_jatuh_tempo)->translatedFormat('d-m-Y') }}</td>
                        <td style="text-align: center;">
                            {{ $d->tanggal_bayar ? \Carbon\Carbon::parse($d->tanggal_bayar)->translatedFormat('d-m-Y') : '-' }}
                        </td>
                        <td style="text-align: center;">{{ $d->metode_pembayaran ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($d->status === 'Lunas')
                                <span class="badge badge-lunas">Lunas</span>
                            @elseif($d->status === 'Belum Bayar')
                                <span class="badge badge-belum">Belum Bayar</span>
                            @elseif($d->status === 'Telat')
                                <span class="badge badge-telat">Telat</span>
                            @else
                                <span class="badge badge-verifikasi">Verifikasi</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">TOTAL PENDAPATAN (LUNAS)</td>
                    <td style="text-align: right;">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                    <td colspan="4" style="background-color: #f8fafc;"></td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        Laporan Keuangan Otomatis KosKu - Dicetak oleh Pemilik pada {{ now()->translatedFormat('d-m-Y H:i') }}
    </div>
</body>
</html>
