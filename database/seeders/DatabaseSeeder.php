<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data awal: hanya akun admin dan daftar dosen.
 * Akun pengajaran/BAAK dibuat admin lewat menu Petugas; mahasiswa mendaftar sendiri.
 * Untuk data contoh uji coba: php artisan db:seed --class=DataContohSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'role' => Role::Admin, 'password' => 'password123'] // otomatis di-hash
        );

        $this->call(DosenSeeder::class);
    }
}
