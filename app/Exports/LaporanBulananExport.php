<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanBulananExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly string $tahun)
    {
    }

    public function collection(): Collection
    {
        $driver = DB::getDriverName();
        $monthExpr = $driver === 'sqlite' ? "strftime('%Y-%m', tanggal_jatuh_tempo)" : "DATE_FORMAT(tanggal_jatuh_tempo, '%Y-%m')";
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', tanggal_jatuh_tempo)" : "DATE_FORMAT(tanggal_jatuh_tempo, '%Y')";

        return DB::table('tagihan_pembayaran')
            ->selectRaw("\n                {$monthExpr} as periode_bulan,\n                COUNT(*) as total_tagihan,\n                SUM(CASE WHEN status = 'Lunas' THEN 1 ELSE 0 END) as jumlah_lunas,\n                SUM(CASE WHEN status = 'Belum Bayar' THEN 1 ELSE 0 END) as jumlah_belum,\n                SUM(CASE WHEN status = 'Telat' THEN 1 ELSE 0 END) as jumlah_telat,\n                SUM(CASE WHEN status = 'Lunas' THEN nominal ELSE 0 END) as pendapatan,\n                SUM(nominal) as total_nominal\n            ")
            ->whereRaw("{$yearExpr} = ?", [$this->tahun])
            ->groupByRaw($monthExpr)
            ->orderBy('periode_bulan')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Periode',
            'Total Tagihan',
            'Lunas',
            'Belum Bayar',
            'Telat',
            'Pendapatan (Lunas)',
            'Target (Total)',
            'Rasio (%)',
        ];
    }

    public function map($row): array
    {
        $namaBulan = '-';
        if (!empty($row->periode_bulan)) {
            $bulanArr = explode('-', $row->periode_bulan);
            if (count($bulanArr) === 2) {
                $namaBulan = Carbon::createFromDate((int) $bulanArr[0], (int) $bulanArr[1], 1)
                    ->translatedFormat('F Y');
            }
        }

        $rasio = $row->total_nominal > 0
            ? round(($row->pendapatan / $row->total_nominal) * 100, 2)
            : 0.0;

        return [
            $namaBulan,
            (int) $row->total_tagihan,
            (int) $row->jumlah_lunas,
            (int) $row->jumlah_belum,
            (int) $row->jumlah_telat,
            (float) $row->pendapatan,
            (float) $row->total_nominal,
            $rasio,
        ];
    }
}
