<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property string $kode
 * @property string $nama
 * @property int    $urutan
 */
class Persyaratan extends Model
{
    protected $table = 'persyaratan';

    protected $fillable = ['kode', 'nama', 'urutan'];

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenMahasiswa::class);
    }
}