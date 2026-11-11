<?php

namespace App\Console\Commands;

use App\Services\OtpService;
use Illuminate\Console\Command;

class TestKirimWaCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah console.
     */
    protected $signature = 'otp:test-wa {target : Nomor WhatsApp tujuan (misal 081234567890)}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Kirim pesan uji coba WhatsApp via Fonnte dan tampilkan status responsnya.';

    public function handle(OtpService $otpService): int
    {
        $input = (string) $this->argument('target');
        $target = $otpService->normalizePhone($input);

        if (! $target) {
            $this->error("Format nomor tidak valid: '{$input}'. Gunakan format 08xxx / 628xxx.");
            return Command::FAILURE;
        }

        $this->info("Mengirim pesan tes ke nomor: {$target} (hasil normalisasi dari '{$input}')...");

        $pesan = "Halo! Ini adalah pesan tes pengiriman WhatsApp dari sistem SIDARYUS via gateway Fonnte. Waktu: " . now()->toDateTimeString();

        $sukses = $otpService->sendWa($target, $pesan);

        if ($sukses) {
            $this->info("✓ Pengiriman berhasil (HTTP 200, status: true)!");
            return Command::SUCCESS;
        }

        $this->error("✗ Pengiriman gagal. Silakan periksa nilai FONNTE_TOKEN di file .env dan pastikan perangkat WhatsApp terhubung di dashboard Fonnte.");
        return Command::FAILURE;
    }
}

