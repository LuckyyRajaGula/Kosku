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
    public function __construct(private readonly string $tahun, private readonly string $bulan = 'all')
    {
    }

    public function collection(): Collection
    {
        $driver = DB::getDriverName();
        $monthExpr = $driver === 'sqlite' ? "strftime('%Y-%m', tanggal_jatuh_tempo)" : "DATE_FORMAT(tanggal_jatuh_tempo, '%Y-%m')";
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', tanggal_jatuh_tempo)" : "DATE_FORMAT(tanggal_jatuh_tempo, '%Y')";
        $onlyMonthExpr = $driver === 'sqlite' ? "strftime('%m', tanggal_jatuh_tempo)" : "DATE_FORMAT(tanggal_jatuh_tempo, '%m')";

        if ($this->bulan === 'all') {
            return DB::table('tagihan_pembayaran')
                ->selectRaw("
                    {$monthExpr} as periode_bulan,
                    COUNT(*) as total_tagihan,
                    SUM(CASE WHEN status = 'Lunas' THEN 1 ELSE 0 END) as jumlah_lunas,
                    SUM(CASE WHEN status = 'Belum Bayar' THEN 1 ELSE 0 END) as jumlah_belum,
                    SUM(CASE WHEN status = 'Telat' THEN 1 ELSE 0 END) as jumlah_telat,
                    SUM(CASE WHEN status = 'Lunas' THEN nominal ELSE 0 END) as pendapatan,
                    SUM(nominal) as total_nominal
                ")
                ->whereRaw("{$yearExpr} = ?", [$this->tahun])
                ->groupByRaw($monthExpr)
                ->orderBy('periode_bulan')
                ->get();
        } else {
            return DB::table('tagihan_pembayaran')
                ->join('penyewa', 'penyewa.id_penyewa', '=', 'tagihan_pembayaran.id_penyewa')
                ->leftJoin('kamar', 'kamar.id_kamar', '=', 'penyewa.id_kamar')
                ->select(
                    'tagihan_pembayaran.*',
                    'penyewa.nama as nama_penyewa',
                    'kamar.no_kamar'
                )
                ->whereRaw("{$yearExpr} = ?", [$this->tahun])
                ->whereRaw("{$onlyMonthExpr} = ?", [$this->bulan])
                ->orderBy('tagihan_pembayaran.tanggal_jatuh_tempo')
                ->get();
        }
    }

    public function headings(): array
    {
        if ($this->bulan === 'all') {
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
        } else {
            return [
                'Nama Penyewa',
                'No Kamar',
                'Periode',
                'Nominal',
                'Jatuh Tempo',
                'Tanggal Bayar',
                'Metode Pembayaran',
                'Status',
            ];
        }
    }

    public function map($row): array
    {
        if ($this->bulan === 'all') {
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
        } else {
            return [
                $row->nama_penyewa,
                $row->no_kamar ?? 'Tanpa Kamar',
                $row->periode,
                (float) $row->nominal,
                $row->tanggal_jatuh_tempo,
                $row->tanggal_bayar ?? '-',
                $row->metode_pembayaran ?? '-',
                $row->status,
            ];
        }
    }
}
