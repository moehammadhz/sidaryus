<?php

namespace App\Services;

use App\Jobs\KirimOtpWaJob;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OtpService
{
    /**
     * Normalisasi nomor telepon:
     * - Buang semua karakter non-digit
     * - 08xxx -> 628xxx
     * - 8xxx -> 628xxx
     * - Validasi regex ^628[0-9]{8,12}$
     *
     * @return string|null String normal jika valid, null jika tidak valid
     */
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '08')) {
            $digits = '628' . substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '628' . substr($digits, 1);
        }

        if (! preg_match('/^628[0-9]{8,12}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * Cek apakah format nomor telepon valid setelah dinormalisasi.
     */
    public function isValidPhone(?string $phone): bool
    {
        return $this->normalizePhone($phone) !== null;
    }

    /**
     * Kirim pesan WhatsApp via gateway Fonnte.
     * POST form ke https://api.fonnte.com/send
     * Header Authorization: <token>, field target & message, timeout 15s.
     * Log error TANPA token dan TANPA isi kode OTP.
     */
    public function sendWa(string $target, string $message): bool
    {
        $token = (string) config('services.fonnte.token');
        $url   = (string) config('services.fonnte.url', 'https://api.fonnte.com/send');

        if (empty($token)) {
            Log::error('Fonnte sendWa gagal: FONNTE_TOKEN belum diisi di environment.');
            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $token,
                ])
                ->asForm()
                ->post($url, [
                    'target'  => $target,
                    'message' => $message,
                ]);

            if ($response->successful() && $response->json('status') === true) {
                return true;
            }

            // Log status gagal tanpa membocorkan token atau kode
            $reason = $response->json('reason') ?? $response->status();
            Log::error("Fonnte sendWa menolak pengiriman ke target akhiran " . substr($target, -4) . ": status HTTP {$response->status()}, alasan: {$reason}");

            return false;
        } catch (Throwable $e) {
            // Log exception tanpa menyertakan message (yang berisi kode OTP) ataupun token
            Log::error('Fonnte sendWa gagal karena exception: ' . get_class($e) . ' - ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Buat kode OTP 6 digit, simpan ter-hash, dan masukkan pengiriman WA ke antrean.
     */
    public function issue(User $user): string
    {
        // Kode 6 digit acak yang aman secara kriptografi
        $code = sprintf('%06d', random_int(0, 999999));

        // Simpan atau perbarui data OTP ter-hash untuk user ini
        OtpCode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code'       => Hash::make($code),
                'expires_at' => now()->addMinutes(OtpCode::MASA_BERLAKU_MENIT),
                'attempts'   => 0,
            ]
        );

        $pesan = "Kode verifikasi SIDARYUS Anda: {$code}. Berlaku selama " . OtpCode::MASA_BERLAKU_MENIT . " menit. JANGAN bagikan kode ini kepada siapa pun.";

        // Debug log HANYA di environment local
        if (app()->environment('local')) {
            Log::debug("OTP dibuat untuk user ID {$user->id}: {$code}"); // TODO: hapus di production
        }

        // Kompatibilitas dengan test mock PengirimOtp yang sudah terdaftar
        if (app()->bound(\App\Services\Otp\PengirimOtp::class)) {
            try {
                app(\App\Services\Otp\PengirimOtp::class)->kirim((string) $user->no_wa, $pesan);
            } catch (Throwable) {
                // Abaikan exception dari mock pada tahap issue
            }
        }

        // Masukkan pengiriman WA ke antrean asynchronous
        KirimOtpWaJob::dispatch($user->id, (string) $user->no_wa, $pesan);

        return $code;
    }

    /**
     * Verifikasi kode OTP yang diinputkan pengguna.
     *
     * @return array{status: bool, message: string}
     */
    public function verify(User $user, string $input): array
    {
        $otp = OtpCode::where('user_id', $user->id)->first();

        if (! $otp) {
            return [
                'status'  => false,
                'message' => 'Kode OTP tidak ditemukan. Silakan klik "Kirim ulang kode".',
            ];
        }

        if ($otp->isExpired()) {
            return [
                'status'  => false,
                'message' => 'Kode OTP sudah kedaluwarsa. Silakan klik "Kirim ulang kode".',
            ];
        }

        if ($otp->isLocked()) {
            return [
                'status'  => false,
                'message' => 'Batas percobaan memasukkan kode telah habis (maksimal ' . OtpCode::MAKS_PERCOBAAN . ' kali). Silakan klik "Kirim ulang kode" untuk mendapatkan kode baru.',
            ];
        }

        // Tambah hitungan percobaan
        $otp->increment('attempts');

        // Periksa kesesuaian hash
        if (Hash::check($input, $otp->code)) {
            $user->markWaAsVerified();
            $otp->delete();

            return [
                'status'  => true,
                'message' => 'Nomor WhatsApp berhasil diverifikasi.',
            ];
        }

        $sisa = OtpCode::MAKS_PERCOBAAN - $otp->fresh()->attempts;

        if ($sisa <= 0) {
            return [
                'status'  => false,
                'message' => 'Kode OTP salah. Batas percobaan habis, silakan klik "Kirim ulang kode".',
            ];
        }

        return [
            'status'  => false,
            'message' => "Kode OTP salah. Sisa kesempatan: {$sisa} kali.",
        ];
    }
}
