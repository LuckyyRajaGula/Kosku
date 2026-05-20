<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTagihanBulanan extends Command
{
    protected $signature = 'tagihan:generate
        {--bulan= : Bulan target (1-12), default bulan ini}
        {--tahun= : Tahun target, default tahun ini}
        {--jatuh-tempo=5 : Tanggal jatuh tempo setiap bulan}';

    protected $description = 'Membuat tagihan sewa bulanan otomatis untuk semua penyewa aktif yang belum memiliki tagihan di bulan target.';

    public function handle(): int
    {
        $bulan = (int) ($this->option('bulan') ?: date('n'));
        $tahun = (int) ($this->option('tahun') ?: date('Y'));
        $tglJatuhTempo = (int) $this->option('jatuh-tempo');

        $periodeName = $this->namaBulan($bulan) . ' ' . $tahun;
        $periodeKey  = sprintf('%04d-%02d', $tahun, $bulan);

        // Tanggal jatuh tempo: hari ke-N di bulan target
        $lastDay = (int) date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
        $dayOfMonth = min($tglJatuhTempo, $lastDay);
        $tanggalJatuhTempo = sprintf('%04d-%02d-%02d', $tahun, $bulan, $dayOfMonth);

        $today = now()->toDateString();

        // Ambil semua penyewa aktif (belum checkout, masih dalam periode kontrak)
        $penyewaAktif = DB::table('penyewa')
            ->join('kamar', 'kamar.id_kamar', '=', 'penyewa.id_kamar')
            ->whereNull('penyewa.tanggal_selesai')
            ->where(function ($q) use ($today) {
                $q->whereNull('penyewa.tanggal_masuk')
                    ->orWhere('penyewa.tanggal_masuk', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('penyewa.tanggal_keluar')
                    ->orWhere('penyewa.tanggal_keluar', '>=', $today);
            })
            ->select('penyewa.id_penyewa', 'penyewa.nama', 'kamar.harga', 'kamar.no_kamar')
            ->get();

        if ($penyewaAktif->isEmpty()) {
            $this->warn('Tidak ada penyewa aktif yang ditemukan.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($penyewaAktif as $p) {
            // Cek apakah tagihan untuk periode ini sudah ada
            $exists = DB::table('tagihan_pembayaran')
                ->where('id_penyewa', $p->id_penyewa)
                ->where('periode', $periodeName)
                ->exists();

            if ($exists) {
                $skipped++;
                $this->line("  ⏭ {$p->nama} (Kamar {$p->no_kamar}) — sudah ada tagihan {$periodeName}");
                continue;
            }

            DB::table('tagihan_pembayaran')->insert([
                'id_penyewa'         => $p->id_penyewa,
                'periode'            => $periodeName,
                'nominal'            => $p->harga,
                'tanggal_jatuh_tempo'=> $tanggalJatuhTempo,
                'status'             => 'Belum Bayar',
            ]);

            $created++;
            $this->info("  ✅ {$p->nama} (Kamar {$p->no_kamar}) — Rp " . number_format($p->harga, 0, ',', '.') . " — jatuh tempo {$tanggalJatuhTempo}");
        }

        $this->newLine();
        $this->info("Selesai! {$created} tagihan dibuat, {$skipped} dilewati (sudah ada).");

        return self::SUCCESS;
    }

    private function namaBulan(int $bulan): string
    {
        $nama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',      6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',   9 => 'September',
            10 => 'Oktober',11 => 'November', 12 => 'Desember',
        ];

        return $nama[$bulan] ?? 'Unknown';
    }
}
