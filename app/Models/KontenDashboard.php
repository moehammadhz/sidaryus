<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $kunci
 * @property array  $items
 */
class KontenDashboard extends Model
{
    protected $table = 'konten_dashboard';

    protected $fillable = ['kunci', 'items'];

    public const KUNCI = ['panduan', 'bebas_teori'];

    public const JUDUL = [
        'panduan'     => 'Panduan Pengisian',
        'bebas_teori' => 'Bebas Teori',
    ];

    /** Isi bawaan, dipakai selama admin belum mengedit */
    public const DEFAULT = [
        'panduan' => [
            'Pastikan login akun Mahasiswa aktif.',
            'Scan dokumen asli.',
            'Format berkas PDF, maksimal 5 MB.',
            'Setelah verifikasi semua berkas, kembali ke halaman upload dokumen untuk cetak bukti pendadaran.',
            'Jangan lupa untuk mengajukan jadwal pendadaran terlebih dahulu.',
        ],
        'bebas_teori' => [
            'Telah menyelesaikan minimal 144 SKS (tanpa nilai E).',
            'Nilai D maksimal 25%.',
            'Nilai mata kuliah umum minimal C.',
            'Indeks Prestasi Kumulatif (IPK) lebih dari 2,00.',
            'Telah menyelesaikan seluruh tugas/praktikum sesuai kurikulum.',
        ],
    ];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    /** @return array<string, string[]> konten tersimpan, dengan isi bawaan sebagai cadangan */
    public static function semua(): array
    {
        $tersimpan = static::whereIn('kunci', self::KUNCI)->get()
            ->mapWithKeys(fn (self $m) => [$m->kunci => $m->items])
            ->all();

        return array_merge(self::DEFAULT, $tersimpan);
    }
}