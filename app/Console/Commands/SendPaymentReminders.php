<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminders extends Command
{
    protected $signature = 'tagihan:reminder {--days=1,3 : Hari sebelum jatuh tempo yang akan diingatkan}';

    protected $description = 'Mengirim pengingat pembayaran tagihan (H-3 & H-1) via email dan opsional WhatsApp.';

    public function handle(): int
    {
        $daysRaw = (string) $this->option('days');
        $daysList = collect(explode(',', $daysRaw))
            ->map(fn ($day) => (int) trim($day))
            ->filter(fn ($day) => $day > 0)
            ->unique()
            ->values();

        if ($daysList->isEmpty()) {
            $this->error('Parameter days tidak valid. Contoh: --days=1,3');
            return self::FAILURE;
        }

        $totalSent = 0;
        $totalSkipped = 0;

        foreach ($daysList as $days) {
            $targetDate = Carbon::now()->addDays($days)->toDateString();

            $tagihanList = DB::table('tagihan_pembayaran')
                ->join('penyewa', 'penyewa.id_penyewa', '=', 'tagihan_pembayaran.id_penyewa')
                ->leftJoin('user', 'user.id_user', '=', 'penyewa.id_user')
                ->where('tagihan_pembayaran.status', '!=', 'Lunas')
                ->whereDate('tagihan_pembayaran.tanggal_jatuh_tempo', $targetDate)
                ->select(
                    'tagihan_pembayaran.id_tagihan',
                    'tagihan_pembayaran.periode',
                    'tagihan_pembayaran.nominal',
                    'tagihan_pembayaran.tanggal_jatuh_tempo',
                    'penyewa.nama as nama_penyewa',
                    'user.email',
                    'user.no_telpon'
                )
                ->get();

            if ($tagihanList->isEmpty()) {
                $this->line("Tidak ada tagihan jatuh tempo H-{$days} ({$targetDate}).");
                continue;
            }

            foreach ($tagihanList as $tagihan) {
                $message = $this->buildMessage($tagihan->nama_penyewa, $tagihan->periode, $tagihan->nominal, $tagihan->tanggal_jatuh_tempo, $days);

                $sent = false;

                if (!empty($tagihan->email)) {
                    Mail::raw($message, function ($mail) use ($tagihan, $days) {
                        $mail->to($tagihan->email)
                            ->subject("Pengingat Tagihan H-{$days} - {$tagihan->periode}");
                    });
                    $sent = true;
                }

                $whatsAppUrl = config('services.whatsapp.webhook_url');
                if (!empty($whatsAppUrl) && !empty($tagihan->no_telpon)) {
                    Http::post($whatsAppUrl, [
                        'phone' => $tagihan->no_telpon,
                        'message' => $message,
                    ]);
                    $sent = true;
                }

                if ($sent) {
                    $totalSent++;
                } else {
                    $totalSkipped++;
                }
            }
        }

        $this->newLine();
        $this->info("Selesai. Terkirim: {$totalSent}, dilewati (tanpa kontak): {$totalSkipped}.");

        return self::SUCCESS;
    }

    private function buildMessage(string $nama, string $periode, $nominal, string $jatuhTempo, int $days): string
    {
        $nominalFormat = number_format((float) $nominal, 0, ',', '.');

        return "Halo {$nama},\n\n" .
            "Ini pengingat tagihan kos Anda untuk periode {$periode}." .
            " Jatuh tempo pada {$jatuhTempo} (H-{$days}).\n" .
            "Total yang harus dibayar: Rp {$nominalFormat}.\n\n" .
            'Mohon lakukan pembayaran sebelum jatuh tempo. Terima kasih.';
    }
}
