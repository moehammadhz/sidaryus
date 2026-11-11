<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Kirim OTP lewat gateway WhatsApp Fonnte (https://fonnte.com).
 * Aktifkan dengan OTP_DRIVER=fonnte dan FONNTE_TOKEN=<token perangkat> di .env.
 * Periksa kembali format API di dokumentasi Fonnte sebelum dipakai di server sungguhan.
 */
class FonntePengirimOtp implements PengirimOtp
{
    public function kirim(string $noWa, string $pesan): void
    {
        $token = config('otp.fonnte.token');

        if (! $token) {
            throw new RuntimeException('FONNTE_TOKEN belum diisi di .env.');
        }

        try {
            $respon = Http::timeout(15)
                ->withHeaders(['Authorization' => $token])
                ->asForm()
                ->post(config('otp.fonnte.url'), ['target' => $noWa, 'message' => $pesan]);
        } catch (Throwable $e) {
            throw new RuntimeException('Gateway WhatsApp tidak dapat dihubungi.', 0, $e);
        }

        if (! $respon->successful() || $respon->json('status') === false) {
            throw new RuntimeException('Gateway WhatsApp menolak pengiriman: ' . ($respon->json('reason') ?? $respon->status()));
        }
    }
}
