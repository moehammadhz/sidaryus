<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                             $id
 * @property int                             $user_id
 * @property int                             $persyaratan_id
 * @property string                          $path
 * @property string                          $nama_asli
 * @property string                          $mime
 * @property int                             $ukuran
 * @property string                          $status
 * @property string|null                     $catatan
 * @property int|null                        $diverifikasi_oleh
 * @property \Illuminate\Support\Carbon      $diunggah_at
 * @property \Illuminate\Support\Carbon|null $diverifikasi_at
 */
class DokumenMahasiswa extends Model
{
    protected $table = 'dokumen_mahasiswa';

    public const MENUNGGU  = 'menunggu';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK   = 'ditolak';

    protected $fillable = [
        'user_id', 'persyaratan_id', 'path', 'nama_asli', 'mime', 'ukuran',
        'status', 'catatan', 'diverifikasi_oleh', 'diverifikasi_at', 'diunggah_at',
    ];

    protected function casts(): array
    {
        return [
            'ukuran'          => 'integer',
            'diunggah_at'     => 'datetime',
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function persyaratan(): BelongsTo
    {
        return $this->belongsTo(Persyaratan::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function ukuranLabel(): string
    {
        return $this->ukuran >= 1048576
            ? number_format($this->ukuran / 1048576, 1, ',', '.') . ' MB'
            : max(1, (int) round($this->ukuran / 1024)) . ' KB';
    }
}