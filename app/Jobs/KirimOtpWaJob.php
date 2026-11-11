<?php

namespace App\Jobs;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class KirimOtpWaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal sebelum masuk failed_jobs.
     */
    public int $tries = 3;

    /**
     * Backoff antar percobaan: 10 detik setelah gagal pertama, 30 detik setelah gagal kedua.
     *
     * @return int[]
     */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function __construct(
        public int $userId,
        public string $target,
        public string $message
    ) {
    }

    public function handle(OtpService $otpService): void
    {
        $user = User::find($this->userId);

        // Lewati pengiriman jika user tidak ditemukan atau sudah terverifikasi
        if (! $user || $user->isWaVerified()) {
            Log::info("KirimOtpWaJob dilewati: user #{$this->userId} sudah terverifikasi atau tidak ditemukan.");
            return;
        }

        $otp = OtpCode::where('user_id', $this->userId)->first();

        // Lewati pengiriman jika baris OTP sudah dihapus atau sudah kedaluwarsa
        if (! $otp || $otp->isExpired()) {
            Log::info("KirimOtpWaJob dilewati: OTP user #{$this->userId} sudah kedaluwarsa atau tidak ada.");
            return;
        }

        $berhasil = $otpService->sendWa($this->target, $this->message);

        if (! $berhasil) {
            // Melempar exception agar worker Laravel menandai job gagal dan menjadwalkan retry sesuai backoff
            throw new RuntimeException("Gagal mengirim WhatsApp OTP ke target.");
        }
    }
}

