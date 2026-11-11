<?php

namespace App\Services\Otp;

/** Pengirim pesan OTP. Ganti implementasinya lewat OTP_DRIVER di .env. */
interface PengirimOtp
{
    /** @throws \RuntimeException jika pesan gagal dikirim */
    public function kirim(string $noWa, string $pesan): void;
}
