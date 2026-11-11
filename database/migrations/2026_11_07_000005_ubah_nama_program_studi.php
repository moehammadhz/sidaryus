<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** "Sipil" dan "Mesin" menjadi "Teknik Sipil" dan "Teknik Mesin". Informatika tetap. */
return new class extends Migration
{
    private const GANTI = ['Sipil' => 'Teknik Sipil', 'Mesin' => 'Teknik Mesin'];

    public function up(): void
    {
        foreach (['users', 'dosen'] as $tabel) {
            foreach (self::GANTI as $lama => $baru) {
                DB::table($tabel)->where('program_studi', $lama)->update(['program_studi' => $baru]);
            }
        }
    }

    public function down(): void
    {
        foreach (['users', 'dosen'] as $tabel) {
            foreach (self::GANTI as $lama => $baru) {
                DB::table($tabel)->where('program_studi', $baru)->update(['program_studi' => $lama]);
            }
        }
    }
};
