<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property string $nama
 * @property bool   $dibuka
 */
class Periode extends Model
{
    protected $table = 'periode';

    protected $fillable = ['nama', 'dibuka'];

    protected function casts(): array
    {
        return ['dibuka' => 'boolean'];
    }

    /** Periode yang sedang menerima pendaftaran yudisium */
    public function scopeDibuka(Builder $query): Builder
    {
        return $query->where('dibuka', true);
    }

    /** Mahasiswa yang terdaftar di periode yudisium ini */
    public function mahasiswa(): HasMany
    {
        return $this->hasMany(User::class, 'periode_id');
    }
}