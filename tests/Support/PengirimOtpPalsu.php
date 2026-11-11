<?php

namespace Tests\Support;

use App\Services\Otp\PengirimOtp;
use RuntimeException;

/** Pengirim OTP untuk test: menyimpan pesan terakhir, bisa dibuat gagal. */
class PengirimOtpPalsu implements PengirimOtp
{
    /** @var array<int, array{0: string, 1: string}> */
    public array $terkirim = [];
    public bool $gagal = false;

    public function kirim(string $noWa, string $pesan): void
    {
        if ($this->gagal) {
            throw new RuntimeException('Gateway mati');
        }
        $this->terkirim[] = [$noWa, $pesan];
    }

    public function kodeTerakhir(): ?string
    {
        $akhir = end($this->terkirim);

        return $akhir && preg_match('/\b(\d{6})\b/', $akhir[1], $m) ? $m[1] : null;
    }
}
