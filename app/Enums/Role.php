<?php

namespace App\Enums;

enum Role: string
{
    case Admin      = 'admin';
    case Pengajaran = 'pengajaran';
    case Baak       = 'baak';
    case Mahasiswa  = 'mahasiswa';

    public function label(): string
    {
        return match ($this) {
            self::Admin      => 'Admin',
            self::Pengajaran => 'Pengajaran',
            self::Baak       => 'BAAK',
            self::Mahasiswa  => 'Mahasiswa',
        };
    }

    /** Nama route halaman awal setiap role setelah login. */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin      => 'admin.dashboard',
            self::Pengajaran => 'petugas.dashboard',
            self::Baak       => 'baak.mahasiswa.index',
            self::Mahasiswa  => 'mahasiswa.dashboard',
        };
    }

    /** @return string[] semua nilai role, berguna untuk validasi */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
