<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bukti bayar yang pernah ditolak, disimpan sebagai riwayat (berkasnya tidak dihapus). */
class RiwayatBuktiBayar extends Model
{
    protected $table = 'riwayat_bukti_bayar';

    protected $fillable = ['jadwal_id', 'path', 'nama', 'mime', 'alasan', 'ditolak_oleh', 'ditolak_at'];

    protected function casts(): array
    {
        return ['ditolak_at' => 'datetime'];
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPendadaran::class, 'jadwal_id');
    }

    public function penolak(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditolak_oleh');
    }
}
