<?php

namespace Database\Seeders;

use App\Models\Periode;
use Illuminate\Database\Seeder;

class PeriodeSeeder extends Seeder
{
    public function run(): void
    {
        // Satu periode yudisium yang sedang dibuka
        Periode::firstOrCreate(['nama' => 'Periode II Yudisium Desember 2026'], ['dibuka' => true]);
    }
}
