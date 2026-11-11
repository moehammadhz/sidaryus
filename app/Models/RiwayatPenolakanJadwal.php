<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Penolakan jadwal beserta salinan data jadwal saat ditolak. */
class RiwayatPenolakanJadwal extends Model
{
    protected $table = 'riwayat_penolakan_jadwal';

    protected $fillable = [
        'jadwal_id', 'judul', 'pembimbing_1', 'pembimbing_2', 'penguji',
        'tanggal_ujian', 'jam_ujian', 'alasan', 'ditolak_oleh', 'ditolak_at',
    ];

    protected function casts(): array
    {
        return ['tanggal_ujian' => 'date', 'ditolak_at' => 'datetime'];
    }

    public function penolak(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditolak_oleh');
    }

    public function waktu(): string
    {
        return $this->tanggal_ujian->locale('id')->translatedFormat('d F Y') . ' · ' . substr((string) $this->jam_ujian, 0, 5) . ' WIB';
    }
}
