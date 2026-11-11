<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property string|null $nidn
 * @property string      $nama
 * @property string      $program_studi
 */
class Dosen extends Model
{
    protected $table = 'dosen';

    protected $fillable = ['nidn', 'nama', 'program_studi'];
}