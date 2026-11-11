<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Mengosongkan database sehingga yang tersisa hanya akun admin dan data master
 * (persyaratan dokumen, konten dashboard, daftar dosen).
 *
 * Jalankan: php artisan sidaryus:kosongkan
 */
class KosongkanData extends Command
{
    protected $signature = 'sidaryus:kosongkan {--paksa : Hapus tanpa konfirmasi}';

    protected $description = 'Hapus semua akun selain admin beserta seluruh data jadwal, dokumen, periode, dan berkasnya';

    /** Tabel yang dikosongkan, urut dari anak ke induk supaya tidak bentrok foreign key */
    private const TABEL = [
        'riwayat_penolakan_jadwal', 'riwayat_bukti_bayar', 'jadwal_pendadaran', 'dokumen_mahasiswa',
        'otp_registrasi', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches',
    ];

    /** Folder berkas unggahan di storage/app/private */
    private const FOLDER = ['dokumen', 'bukti-bayar', 'undangan'];

    public function handle(): int
    {
        $admin = User::where('role', Role::Admin->value)->orderBy('id')->get();
        $lain  = User::where('role', '!=', Role::Admin->value)->count();

        $baris = [];
        foreach ([...self::TABEL, 'periode'] as $t) {
            $baris[] = [$t, DB::table($t)->count()];
        }
        $baris[] = ['users (selain admin)', $lain];

        $this->warn('Data berikut akan DIHAPUS PERMANEN:');
        $this->table(['Tabel', 'Jumlah baris'], $baris);
        $this->line('Berkas unggahan di storage/app/private/{' . implode(',', self::FOLDER) . '} ikut dihapus.');
        $this->info('Tetap ada: akun admin ' . ($admin->isEmpty() ? '(belum ada, akan dibuat)' : '(' . $admin->pluck('username')->join(', ') . ')')
            . ', persyaratan dokumen, konten dashboard, dan daftar dosen.');

        if (! $this->option('paksa') && ! $this->confirm('Lanjutkan?', false)) {
            $this->warn('Dibatalkan, tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            foreach (self::TABEL as $t) {
                DB::table($t)->delete();
            }
            User::where('role', '!=', Role::Admin->value)->delete();
            DB::table('periode')->delete();
        });

        foreach (self::FOLDER as $f) {
            Storage::disk('local')->deleteDirectory($f);
        }

        // nomor id mulai lagi dari awal untuk tabel yang sudah kosong (MySQL)
        if (DB::getDriverName() === 'mysql') {
            foreach ([...self::TABEL, 'periode', 'users'] as $t) {
                DB::statement("ALTER TABLE `{$t}` AUTO_INCREMENT = 1");
            }
        }

        if ($admin->isEmpty()) {
            User::create(['name' => 'Administrator', 'username' => 'admin', 'password' => 'password123', 'role' => Role::Admin->value]);
            $this->warn('Akun admin dibuat: username admin, password password123. Segera ganti password-nya.');
        }

        $this->info('Selesai. Akun yang tersisa: ' . User::pluck('username')->join(', ') . '.');
        $this->line('Semua sesi login ikut terhapus, jadi muat ulang browser lalu login lagi.');

        return self::SUCCESS;
    }
}
