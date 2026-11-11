<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

/** Untuk pengembangan: pesan tidak dikirim, hanya ditulis ke log aplikasi. */
class LogPengirimOtp implements PengirimOtp
{
    public function kirim(string $noWa, string $pesan): void
    {
        Log::info("[OTP WhatsApp ke {$noWa}] {$pesan}");
    }
}
