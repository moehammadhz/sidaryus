<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\DokumenMahasiswa;
use App\Models\Persyaratan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DokumenSeeder extends Seeder
{
    public function run(): void
    {
        $syarat  = Persyaratan::orderBy('urutan')->orderBy('id')->get()->all();
        $adminId = User::where('username', 'admin')->value('id');

        // [NIM, nama, prodi, angkatan, kelas, (tidak dipakai), [disetujui, menunggu, ditolak]]
        $data = [
            ['21110003', 'Citra Dewi',     'Teknik Sipil', 2021, 'A', null, [6, 0, 0]],
            ['21110004', 'Dani Kurniawan', 'Informatika',  2021, 'A', null, [6, 0, 0]],
            ['22110005', 'Eka Putri',      'Teknik Sipil', 2022, 'B', null, [6, 0, 0]],
        ];

        foreach ($data as $i => [$nim, $nama, $prodi, $angkatan, $kelas, $idx, $hitung]) {
            $user = User::updateOrCreate(['username' => $nim], [
                'name'          => $nama,
                'role'          => Role::Mahasiswa,
                'program_studi' => $prodi,
                'kelas'         => $kelas,
                'angkatan'      => $angkatan,
                'password'      => 'password123',
            ]);

            if ($user->dokumen()->exists()) {
                continue; // aman dijalankan ulang, tidak menggandakan berkas
            }

            [$ok, $tunggu, $tolak] = $hitung;
            $status = array_merge(
                array_fill(0, $ok, DokumenMahasiswa::DISETUJUI),
                array_fill(0, $tunggu, DokumenMahasiswa::MENUNGGU),
                array_fill(0, $tolak, DokumenMahasiswa::DITOLAK),
            );

            foreach (array_slice($syarat, 0, count($status)) as $k => $p) {
                $berkas = strtolower($p->kode) . '-' . $nim . '.pdf';
                $path   = "dokumen/{$user->id}/{$berkas}";
                $isi    = $this->pdf("{$p->kode} - {$nim}");
                $final  = $status[$k] !== DokumenMahasiswa::MENUNGGU;

                Storage::disk('local')->put($path, $isi);

                DokumenMahasiswa::create([
                    'user_id'           => $user->id,
                    'persyaratan_id'    => $p->id,
                    'path'              => $path,
                    'nama_asli'         => $berkas,
                    'mime'              => 'application/pdf',
                    'ukuran'            => strlen($isi),
                    'status'            => $status[$k],
                    'catatan'           => $status[$k] === DokumenMahasiswa::DITOLAK ? 'Berkas buram, mohon unggah ulang.' : null,
                    'diverifikasi_oleh' => $final ? $adminId : null,
                    'diverifikasi_at'   => $final ? now() : null,
                    'diunggah_at'       => now()->subDays($i * 2 + $k)->setTime(9 + ($k % 6), 15),
                ]);
            }
        }
    }

    /** PDF satu halaman sederhana, hanya untuk berkas contoh */
    private function pdf(string $teks): string
    {
        $teks   = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $teks);
        $stream = "BT /F1 20 Tf 60 760 Td (Berkas contoh: {$teks}) Tj ET";

        $objek = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $out    = "%PDF-1.4\n";
        $offset = [];
        foreach ($objek as $n => $isi) {
            $offset[] = strlen($out);
            $out .= ($n + 1) . " 0 obj\n{$isi}\nendobj\n";
        }

        $xref = strlen($out);
        $out .= "xref\n0 " . (count($objek) + 1) . "\n0000000000 65535 f \n";
        foreach ($offset as $o) {
            $out .= sprintf("%010d 00000 n \n", $o);
        }
        $out .= 'trailer
<< /Size ' . (count($objek) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $out;
    }
}