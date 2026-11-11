<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BersihkanOtpKedaluwarsa extends Command
{
    /**
     * Nama dan tanda tangan perintah console.
     */
    protected $signature = 'otp:clean-expired';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Hapus baris otp_codes yang sudah kedaluwarsa dan bersihkan failed_jobs lama.';

    public function handle(): int
    {
        $dihapus = OtpCode::where('expires_at', '<', now())->delete();
        $this->info("Berhasil menghapus {$dihapus} kode OTP yang kedaluwarsa.");

        // Bersihkan failed_jobs yang berumur lebih dari 24 jam untuk melindungi payload isi pesan
        Artisan::call('queue:prune-failed', ['--hours' => 24]);
        $this->info('Pembersihan failed_jobs lama selesai.');

        return Command::SUCCESS;
    }
}

