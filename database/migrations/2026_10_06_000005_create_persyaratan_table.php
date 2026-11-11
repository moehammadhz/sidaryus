<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persyaratan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->unsignedInteger('urutan')->default(0)->index();
            $table->timestamps();
        });

        // Data awal, diambil dari daftar syarat dokumen yang sudah ada
        $awal = [
            ['KTM',  'Kartu Tanda Mahasiswa'],
            ['KTP',  'Scan Kartu Tanda Penduduk'],
            ['SKBT', 'Scan Keterangan Bebas Teori dari Bagian Akademik'],
            ['SKTA', 'Scan SK Tugas Akhir/Tesis (Minimal 3 bulan sebelum Ujian)'],
            ['DHS',  'Scan Daftar Hadir Mengikuti Seminar TA/Tesis (Minimal 6 Kali)'],
            ['BSH',  'Scan Bukti Seminar Hasil'],
        ];

        $now = now();
        foreach ($awal as $i => [$kode, $nama]) {
            DB::table('persyaratan')->insert([
                'kode'       => $kode,
                'nama'       => $nama,
                'urutan'     => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('persyaratan');
    }
};