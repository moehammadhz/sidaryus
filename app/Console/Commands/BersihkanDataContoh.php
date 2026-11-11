<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\JadwalPendadaran;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Menghapus data contoh dari seeder lama tanpa mengosongkan database:
 * jadwal contoh tanpa akun, akun mahasiswa contoh, periode contoh yang tidak dipakai,
 * dan berkas di storage yang pemiliknya sudah tidak ada.
 *
 * Jalankan: php artisan sidaryus:bersihkan-contoh
 */
class BersihkanDataContoh extends Command
{
    protected $signature = 'sidaryus:bersihkan-contoh {--paksa : Hapus tanpa konfirmasi}';

    protected $description = 'Hapus data contoh (dummy) dari seeder lama, data asli tetap aman';

    /** NIM mahasiswa contoh dari seeder lama (8 dan 10 digit). Citra, Dani, dan Eka tidak termasuk. */
    private const NIM_CONTOH = [
        '21010001', '20110001', '20110002', '22110006', '23110007', '23110008', '23110009', '24110010',
        '2021010001', '2020110001', '2020110002', '2022110006', '2023110007', '2023110008', '2023110009', '2024110010',
    ];

    /** Nama periode contoh dari seeder lama */
    private const PERIODE_CONTOH = [
        'Periode I Yudisium Januari 2026', 'Periode II Yudisium April 2026', 'Periode II Yudisium Mei 2026',
        'Periode III Yudisium Agustus 2026', 'Periode III Yudisium Januari 2026', 'Periode I Yudisium November 2026',
    ];

    public function handle(): int
    {
        $jadwal  = JadwalPendadaran::whereNull('user_id')->get();
        $akun    = User::where('role', Role::Mahasiswa->value)->whereIn('username', self::NIM_CONTOH)->get();
        $periode = Periode::whereIn('nama', self::PERIODE_CONTOH)->whereDoesntHave('mahasiswa')->get();

        $this->info('Data contoh yang akan dihapus:');
        $this->table(['Jenis', 'Jumlah', 'Rincian'], [
            ['Jadwal tanpa akun mahasiswa', $jadwal->count(), $jadwal->pluck('nama')->take(6)->join(', ') . ($jadwal->count() > 6 ? ', ...' : '')],
            ['Akun mahasiswa contoh', $akun->count(), $akun->map(fn ($u) => "{$u->username} {$u->name}")->join(', ')],
            ['Periode contoh tanpa pendaftar', $periode->count(), $periode->pluck('nama')->join(', ')],
        ]);

        if ($jadwal->isEmpty() && $akun->isEmpty() && $periode->isEmpty()) {
            $this->line('Tidak ada data contoh di database.');
        } elseif (! $this->option('paksa') && ! $this->confirm('Hapus data di atas? Data lain (termasuk akun hasil registrasi) tidak disentuh', false)) {
            $this->warn('Dibatalkan, tidak ada yang dihapus.');

            return self::SUCCESS;
        } else {
            DB::transaction(function () use ($jadwal, $akun, $periode) {
                JadwalPendadaran::whereIn('id', $jadwal->pluck('id'))->delete();
                // jadwal dan dokumen milik akun contoh ikut terhapus lewat relasi database
                JadwalPendadaran::whereIn('user_id', $akun->pluck('id'))->delete();
                User::whereIn('id', $akun->pluck('id'))->delete();
                Periode::whereIn('id', $periode->pluck('id'))->delete();
            });
            $this->info('Data contoh dihapus.');
        }

        $berkas = $this->bersihkanBerkasYatim();
        $this->info("Folder berkas tanpa pemilik yang dibersihkan: {$berkas}.");

        return self::SUCCESS;
    }

    /** Folder dokumen/bukti bayar milik akun yang sudah tidak ada, dan undangan milik jadwal yang sudah tidak ada */
    private function bersihkanBerkasYatim(): int
    {
        $disk = Storage::disk('local');
        $userAda = User::pluck('id')->map(fn ($id) => (string) $id)->all();
        $jadwalAda = JadwalPendadaran::pluck('id')->map(fn ($id) => (string) $id)->all();
        $dipakai = DB::table('riwayat_bukti_bayar')->pluck('path')
            ->merge(JadwalPendadaran::whereNotNull('bukti_bayar_path')->pluck('bukti_bayar_path'))->all();

        $n = 0;
        foreach (['dokumen' => $userAda, 'bukti-bayar' => $userAda, 'undangan' => $jadwalAda] as $akar => $ada) {
            foreach ($disk->directories($akar) as $folder) {
                $id = basename($folder);
                if (in_array($id, $ada, true)) {
                    continue;
                }
                // jangan hapus folder yang masih dirujuk riwayat atau jadwal
                $masihDipakai = collect($dipakai)->contains(fn ($p) => str_starts_with((string) $p, $folder . '/'));
                if (! $masihDipakai) {
                    $disk->deleteDirectory($folder);
                    $n++;
                }
            }
        }

        return $n;
    }
}
