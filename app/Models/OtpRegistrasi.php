<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pendaftaran yang menunggu kode OTP. Akun baru dibuat setelah kode benar.
 *
 * @property int                        $id
 * @property string                     $no_wa
 * @property array                      $data
 * @property string                     $kode_hash
 * @property \Illuminate\Support\Carbon $berlaku_sampai
 * @property int                        $percobaan
 * @property \Illuminate\Support\Carbon $dikirim_at
 */
class OtpRegistrasi extends Model
{
    protected $table = 'otp_registrasi';

    /** Masa berlaku kode, batas salah ketik, dan jeda kirim ulang */
    public const BERLAKU_MENIT    = 5;
    public const MAKS_PERCOBAAN   = 5;
    public const JEDA_KIRIM_DETIK = 60;

    protected $fillable = ['no_wa', 'data', 'kode_hash', 'berlaku_sampai', 'percobaan', 'dikirim_at'];

    protected $hidden = ['data', 'kode_hash'];

    protected function casts(): array
    {
        return [
            'data'           => 'encrypted:array', // berisi hash password dan data diri, jadi dienkripsi
            'berlaku_sampai' => 'datetime',
            'dikirim_at'     => 'datetime',
            'percobaan'      => 'integer',
        ];
    }

    public function kedaluwarsa(): bool
    {
        return $this->berlaku_sampai->isPast();
    }

    public function terkunci(): bool
    {
        return $this->percobaan >= self::MAKS_PERCOBAAN;
    }

    /** Sisa detik sebelum kode boleh dikirim ulang */
    public function sisaJeda(): int
    {
        return max(0, self::JEDA_KIRIM_DETIK - (int) $this->dikirim_at->diffInSeconds(now()));
    }
}
