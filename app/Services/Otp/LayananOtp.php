<?php

namespace App\Services\Otp;

use App\Models\OtpRegistrasi;
use Illuminate\Support\Facades\Hash;

/** Membuat, mengirim, dan memeriksa kode OTP registrasi. */
class LayananOtp
{
    public function __construct(private PengirimOtp $pengirim)
    {
    }

    /**
     * Simpan isian pendaftaran dan kirim kode. Kode yang baru dibuat dikembalikan
     * (dipakai untuk mode uji coba yang menampilkan kode di layar).
     *
     * @return array{0: OtpRegistrasi, 1: string}
     */
    public function mulai(string $noWa, array $data): array
    {
        // Pendaftaran lama yang belum selesai untuk nomor ini tidak berlaku lagi
        OtpRegistrasi::where('no_wa', $noWa)->delete();

        $kode = $this->kodeBaru();
        $otp = OtpRegistrasi::create([
            'no_wa'          => $noWa,
            'data'           => $data,
            'kode_hash'      => Hash::make($kode),
            'berlaku_sampai' => now()->addMinutes(OtpRegistrasi::BERLAKU_MENIT),
            'percobaan'      => 0,
            'dikirim_at'     => now(),
        ]);

        $this->kirim($otp, $kode);

        return [$otp, $kode];
    }

    /** @return string kode baru */
    public function kirimUlang(OtpRegistrasi $otp): string
    {
        $kode = $this->kodeBaru();
        $otp->update([
            'kode_hash'      => Hash::make($kode),
            'berlaku_sampai' => now()->addMinutes(OtpRegistrasi::BERLAKU_MENIT),
            'percobaan'      => 0,
            'dikirim_at'     => now(),
        ]);

        $this->kirim($otp, $kode);

        return $kode;
    }

    /** Periksa kode. Setiap salah ketik menambah hitungan percobaan. */
    public function cocok(OtpRegistrasi $otp, string $kode): bool
    {
        if ($otp->kedaluwarsa() || $otp->terkunci()) {
            return false;
        }

        if (Hash::check($kode, $otp->kode_hash)) {
            return true;
        }

        $otp->increment('percobaan');

        return false;
    }

    private function kodeBaru(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function kirim(OtpRegistrasi $otp, string $kode): void
    {
        $this->pengirim->kirim($otp->no_wa,
            "Kode verifikasi SIDARYUS Anda: {$kode}\n"
            . 'Berlaku ' . OtpRegistrasi::BERLAKU_MENIT . " menit. Jangan berikan kode ini kepada siapa pun.");
    }
}
