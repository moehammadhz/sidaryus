<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk uji coba (jangan dijalankan di server sungguhan):
 * akun pengajaran & BAAK, satu periode, dan tiga mahasiswa (Citra, Dani, Eka).
 * Jalankan: php artisan db:seed --class=DataContohSeeder
 * Hapus lagi: php artisan sidaryus:kosongkan
 */
class DataContohSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        foreach ([['Staf Pengajaran', 'pengajaran', Role::Pengajaran], ['Staf BAAK', 'baak', Role::Baak]] as [$nama, $username, $role]) {
            User::updateOrCreate(['username' => $username], ['name' => $nama, 'role' => $role, 'password' => 'password123']);
        }

        $this->call([PeriodeSeeder::class, DokumenSeeder::class, AlurPendadaranSeeder::class]);
    }
}
